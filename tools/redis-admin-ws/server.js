'use strict';

const crypto = require('crypto');
const http = require('http');
const path = require('path');
const { URL } = require('url');
const dotenv = require('dotenv');
const { WebSocketServer } = require('ws');
const { createClient } = require('redis');

dotenv.config({ path: path.resolve(__dirname, '..', '..', '.env') });

const websocketUrl = new URL(process.env.REDIS_WEBSOCKET_URL || process.env['redis.websocket.url'] || 'ws://127.0.0.1:8081');
const intervalMs = Math.max(2000, Number(process.env.REDIS_WEBSOCKET_INTERVAL_MS || process.env['redis.websocket.intervalMs'] || 2000));
const slowlogLimit = Math.max(1, Number(process.env.REDIS_SLOWLOG_LIMIT || process.env['redis.slowlogLimit'] || 25));
const scanCount = Math.max(50, Number(process.env.REDIS_SCAN_COUNT || process.env['redis.scanCount'] || 200));
const sampleLimit = Math.max(100, Number(process.env.REDIS_SCAN_SAMPLE_LIMIT || process.env['redis.scanSampleLimit'] || 500));
const adminSecret = String(process.env.REDIS_WEBSOCKET_SECRET || process.env['redis.websocket.secret'] || '').trim();
const allowedOrigin = String(process.env.APP_BASE_URL || process.env['app.baseURL'] || '').trim();

if (! adminSecret) {
    throw new Error('redis.websocket.secret must be configured before starting the Redis WebSocket server.');
}

const redisClient = createClient({
    socket: {
        host: process.env.REDIS_HOST || process.env['redis.host'] || '127.0.0.1',
        port: Number(process.env.REDIS_PORT || process.env['redis.port'] || 6379),
        connectTimeout: Number(process.env.REDIS_CONNECT_TIMEOUT_MS || 1500),
    },
    password: process.env.REDIS_PASSWORD || process.env['redis.password'] || undefined,
    database: Number(process.env.REDIS_DATABASE || process.env['redis.database'] || 0),
});

redisClient.on('error', (error) => {
    console.error('[redis-admin-ws] Redis error:', error.message);
});

function envList(primary, fallback) {
    const raw = String(process.env[primary] || process.env[fallback] || '').trim();
    return raw.split(',').map((item) => item.trim()).filter(Boolean);
}

function parseInfo(raw) {
    const info = {};
    String(raw || '')
        .split('\n')
        .map((line) => line.trim())
        .filter((line) => line && ! line.startsWith('#'))
        .forEach((line) => {
            const separatorIndex = line.indexOf(':');
            if (separatorIndex === -1) {
                return;
            }
            const key = line.slice(0, separatorIndex);
            const value = line.slice(separatorIndex + 1);
            info[key] = value;
        });
    return info;
}

function formatBytes(bytes) {
    const value = Number(bytes || 0);
    if (! value) {
        return '0 B';
    }
    const units = ['B', 'KB', 'MB', 'GB', 'TB'];
    const power = Math.min(Math.floor(Math.log(value) / Math.log(1024)), units.length - 1);
    return `${(value / (1024 ** power)).toFixed(2)} ${units[power]}`;
}

function signPayload(json) {
    return crypto.createHmac('sha256', adminSecret).update(json).digest('hex');
}

function decodeToken(token) {
    const [encodedPayload, signature] = String(token || '').split('.');
    if (! encodedPayload || ! signature) {
        throw new Error('Missing token components.');
    }

    const normalized = encodedPayload.replace(/-/g, '+').replace(/_/g, '/');
    const payloadJson = Buffer.from(normalized.padEnd(Math.ceil(normalized.length / 4) * 4, '='), 'base64').toString('utf8');
    const expected = signPayload(payloadJson);

    if (! crypto.timingSafeEqual(Buffer.from(signature), Buffer.from(expected))) {
        throw new Error('Invalid token signature.');
    }

    const payload = JSON.parse(payloadJson);
    if (! payload.expires_at || Number(payload.expires_at) < Math.floor(Date.now() / 1000)) {
        throw new Error('Expired token.');
    }

    return payload;
}

function isOriginAllowed(origin) {
    if (! origin || ! allowedOrigin) {
        return true;
    }

    try {
        return new URL(origin).origin === new URL(allowedOrigin).origin;
    } catch {
        return false;
    }
}

async function safeNumberCommand(command) {
    const value = await redisClient.sendCommand(command);
    return Number(value || 0);
}

async function getSlowlog() {
    const entries = await redisClient.sendCommand(['SLOWLOG', 'GET', String(slowlogLimit)]);
    if (! Array.isArray(entries)) {
        return [];
    }

    return entries.map((entry) => ({
        id: Number(entry[0] || 0),
        timestamp: entry[1] ? new Date(Number(entry[1]) * 1000).toISOString() : null,
        duration_microseconds: Number(entry[2] || 0),
        command: Array.isArray(entry[3]) ? entry[3].map((part) => String(part)).join(' ') : '',
        client: String(entry[4] || ''),
        name: String(entry[5] || ''),
    }));
}

async function scanPattern(pattern, limit, describe = false) {
    let cursor = '0';
    const items = [];

    do {
        const response = await redisClient.sendCommand(['SCAN', cursor, 'MATCH', pattern, 'COUNT', String(scanCount)]);
        cursor = String(response[0] || '0');
        const batch = Array.isArray(response[1]) ? response[1] : [];

        for (const key of batch) {
            if (describe) {
                const ttl = Number(await redisClient.sendCommand(['TTL', key]));
                const type = String(await redisClient.sendCommand(['TYPE', key]));
                let memoryUsage = 0;
                try {
                    memoryUsage = Number(await redisClient.sendCommand(['MEMORY', 'USAGE', key]));
                } catch {
                    memoryUsage = 0;
                }

                items.push({
                    key,
                    ttl,
                    ttl_human: ttl === -1 ? 'No expiry' : (ttl === -2 ? 'Missing' : `${ttl}s`),
                    type,
                    memory_usage: memoryUsage,
                });
            } else {
                items.push(key);
            }

            if (items.length >= limit || items.length >= sampleLimit) {
                return items;
            }
        }
    } while (cursor !== '0');

    return items;
}

function derivePrefix(key) {
    const parts = String(key).split(':');
    if (parts.length <= 1) {
        return key;
    }
    return `${parts.slice(0, Math.min(parts.length, 2)).join(':')}:`;
}

async function sampleTtlStats() {
    let cursor = '0';
    let sampled = 0;
    let withTtl = 0;
    let withoutTtl = 0;
    let expired = 0;

    do {
        const response = await redisClient.sendCommand(['SCAN', cursor, 'COUNT', String(scanCount)]);
        cursor = String(response[0] || '0');
        const batch = Array.isArray(response[1]) ? response[1] : [];

        for (const key of batch) {
            const ttl = Number(await redisClient.sendCommand(['TTL', key]));
            sampled += 1;
            if (ttl === -1) {
                withoutTtl += 1;
            } else if (ttl === -2) {
                expired += 1;
            } else {
                withTtl += 1;
            }

            if (sampled >= sampleLimit) {
                return { sampled, with_ttl: withTtl, without_ttl: withoutTtl, expired, without_ttl_warning: withoutTtl > 0 };
            }
        }
    } while (cursor !== '0');

    return { sampled, with_ttl: withTtl, without_ttl: withoutTtl, expired, without_ttl_warning: withoutTtl > 0 };
}

async function countPrefixes(prefixes) {
    let total = 0;
    for (const prefix of prefixes) {
        const keys = await scanPattern(`${prefix}*`, sampleLimit, false);
        total += keys.length;
    }
    return total;
}

async function readFirstCounter(keys) {
    for (const key of keys) {
        const value = await redisClient.get(key);
        if (value !== null && value !== undefined && value !== '' && ! Number.isNaN(Number(value))) {
            return Number(value);
        }
    }
    return 0;
}

async function buildSnapshot() {
    const info = parseInfo(await redisClient.sendCommand(['INFO']));
    const usedMemory = Number(info.used_memory || 0);
    const maxmemory = Number(info.maxmemory || 0);
    const hits = Number(info.keyspace_hits || 0);
    const misses = Number(info.keyspace_misses || 0);
    const ttl = await sampleTtlStats();
    const sampledKeys = await scanPattern('*', 25, true);
    const prefixSummary = {};
    sampledKeys.forEach((item) => {
        const prefix = derivePrefix(item.key);
        prefixSummary[prefix] = (prefixSummary[prefix] || 0) + 1;
    });

    const overview = {
        uptime_seconds: Number(info.uptime_in_seconds || 0),
        connected_clients: Number(info.connected_clients || 0),
        used_memory: usedMemory,
        used_memory_human: formatBytes(usedMemory),
        maxmemory,
        memory_usage_percent: maxmemory > 0 ? Number(((usedMemory / maxmemory) * 100).toFixed(2)) : null,
        evicted_keys: Number(info.evicted_keys || 0),
        expired_keys: Number(info.expired_keys || 0),
        dbsize: await safeNumberCommand(['DBSIZE']),
        redis_version: String(info.redis_version || 'unknown'),
        mode: String(info.redis_mode || 'standalone'),
        status: 'LIVE',
        generated_at: new Date().toISOString(),
    };

    const performance = {
        commands_per_sec: Number(info.instantaneous_ops_per_sec || 0),
        hits,
        misses,
        hit_rate: Number((((hits + misses) > 0 ? (hits / (hits + misses)) : 0) * 100).toFixed(2)),
        input_kbps: Number(Number(info.instantaneous_input_kbps || 0).toFixed(2)),
        output_kbps: Number(Number(info.instantaneous_output_kbps || 0).toFixed(2)),
        rejected_connections: Number(info.rejected_connections || 0),
        total_connections_received: Number(info.total_connections_received || 0),
        total_commands_processed: Number(info.total_commands_processed || 0),
        latest_fork_usec: Number(info.latest_fork_usec || 0),
    };

    const memory = {
        used_memory: usedMemory,
        used_memory_human: formatBytes(usedMemory),
        used_memory_peak: Number(info.used_memory_peak || 0),
        used_memory_peak_human: formatBytes(Number(info.used_memory_peak || 0)),
        used_memory_rss: Number(info.used_memory_rss || 0),
        used_memory_rss_human: formatBytes(Number(info.used_memory_rss || 0)),
        mem_fragmentation_ratio: Number(Number(info.mem_fragmentation_ratio || 0).toFixed(2)),
        maxmemory_policy: String(info.maxmemory_policy || 'noeviction'),
        ttl,
        allocator: String(info.mem_allocator || 'unknown'),
    };

    const iptv = {
        active_users: await countPrefixes(envList('REDIS_IPTV_USER_PREFIXES', 'redis.iptv.userPrefixes')),
        active_streams: await countPrefixes(envList('REDIS_IPTV_STREAM_PREFIXES', 'redis.iptv.streamPrefixes')),
        cache_hits: {
            epg: await readFirstCounter(envList('REDIS_IPTV_EPG_HIT_KEYS', 'redis.iptv.epgHitKeys')),
            vod: await readFirstCounter(envList('REDIS_IPTV_VOD_HIT_KEYS', 'redis.iptv.vodHitKeys')),
        },
    };

    const alerts = [];
    if (overview.memory_usage_percent !== null && overview.memory_usage_percent > 80) {
        alerts.push({ level: 'warning', message: 'Memory usage exceeds 80% of maxmemory.' });
    }
    if (overview.evicted_keys > 0) {
        alerts.push({ level: 'danger', message: 'Redis has evicted keys. Review memory pressure immediately.' });
    }
    if (performance.latest_fork_usec > 500000) {
        alerts.push({ level: 'warning', message: 'High Redis latency detected from fork duration.' });
    }
    if (ttl.without_ttl_warning) {
        alerts.push({ level: 'warning', message: 'Sampled keys without TTL detected.' });
    }

    return {
        overview,
        performance,
        memory,
        keys: {
            dbsize: overview.dbsize,
            sample_size: sampledKeys.length,
            sampled_prefixes: prefixSummary,
            recent_keys: sampledKeys.slice(0, 10),
        },
        slowlog: await getSlowlog(),
        iptv,
        alerts,
    };
}

let snapshotInFlight = null;

async function getSnapshot() {
    if (snapshotInFlight) {
        return snapshotInFlight;
    }

    snapshotInFlight = buildSnapshot()
        .finally(() => {
            snapshotInFlight = null;
        });

    return snapshotInFlight;
}

const server = http.createServer((request, response) => {
    response.writeHead(404);
    response.end('Not found');
});

const wss = new WebSocketServer({ server, path: websocketUrl.pathname === '' ? '/' : websocketUrl.pathname });

wss.on('connection', async (socket, request) => {
    const requestUrl = new URL(request.url, `http://${request.headers.host}`);
    const token = requestUrl.searchParams.get('token');

    try {
        if (! isOriginAllowed(request.headers.origin)) {
            throw new Error('Origin not allowed.');
        }

        socket.admin = decodeToken(token);
        const snapshot = await getSnapshot();
        socket.send(JSON.stringify({ type: 'snapshot', data: snapshot }));
    } catch (error) {
        socket.send(JSON.stringify({ type: 'error', message: error.message }));
        socket.close(1008, 'Unauthorized');
    }
});

setInterval(async () => {
    if (wss.clients.size === 0) {
        return;
    }

    try {
        const snapshot = await getSnapshot();
        const payload = JSON.stringify({ type: 'snapshot', data: snapshot });
        wss.clients.forEach((socket) => {
            if (socket.readyState === socket.OPEN) {
                socket.send(payload);
            }
        });
    } catch (error) {
        const payload = JSON.stringify({ type: 'error', message: error.message });
        wss.clients.forEach((socket) => {
            if (socket.readyState === socket.OPEN) {
                socket.send(payload);
            }
        });
    }
}, intervalMs);

(async () => {
    await redisClient.connect();
    server.listen(Number(websocketUrl.port || 8081), websocketUrl.hostname, () => {
        console.log(`[redis-admin-ws] listening on ${websocketUrl.href}`);
    });
})();