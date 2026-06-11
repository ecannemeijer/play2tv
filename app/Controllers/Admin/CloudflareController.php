<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use CodeIgniter\Controller;

class CloudflareController extends Controller
{
    private const CF_BASE    = 'https://api.cloudflare.com/client/v4';
    private const CF_GRAPHQL = 'https://api.cloudflare.com/client/v4/graphql';

    public function __construct()
    {
        helper(['url', 'number']);
    }

    public function index()
    {
        $db = db_connect();
        $snapshots = $db->table('cloudflare_analytics')
            ->orderBy('snapshot_date', 'DESC')->limit(30)->get()->getResultArray();
        $chartSnapshots = array_reverse($snapshots);
        $latest = $snapshots[0] ?? null;

        $totals = $db->table('cloudflare_analytics')->select([
            'SUM(total_requests) AS total_requests', 'SUM(page_views) AS page_views',
            'SUM(unique_visitors) AS unique_visitors', 'SUM(bandwidth_bytes) AS bandwidth_bytes',
            'SUM(threats_blocked) AS threats_blocked', 'SUM(bot_requests) AS bot_requests',
            'SUM(cached_requests) AS cached_requests', 'SUM(uncached_requests) AS uncached_requests',
        ])->get()->getRowArray();

        $cacheHitRate = 0;
        $tc = (int)($totals['cached_requests']??0) + (int)($totals['uncached_requests']??0);
        if ($tc > 0) $cacheHitRate = round(((int)($totals['cached_requests']??0) / $tc) * 100, 1);

        $countryTotals = []; $browserTotals = []; $subdomainTotals = [];
        foreach ($snapshots as $s) {
            foreach (['countries_data'=>'countryTotals','browser_data'=>'browserTotals','subdomain_data'=>'subdomainTotals'] as $col => $var) {
                if (empty($s[$col])) continue;
                $d = json_decode((string)$s[$col], true);
                if (!is_array($d)) continue;
                foreach ($d as $k => $v) { ${$var}[$k] = (${$var}[$k] ?? 0) + (int)$v; }
            }
        }
        arsort($countryTotals); arsort($browserTotals); arsort($subdomainTotals);

        $chartLabels=$chartPageViews=$chartVisitors=$chartRequests=$chartThreats=$chartBandwidth=[];
        foreach ($chartSnapshots as $s) {
            $chartLabels[]=$s['snapshot_date']; $chartPageViews[]=(int)($s['page_views']??0);
            $chartVisitors[]=(int)($s['unique_visitors']??0); $chartRequests[]=(int)($s['total_requests']??0);
            $chartThreats[]=(int)($s['threats_blocked']??0); $chartBandwidth[]=round(((int)($s['bandwidth_bytes']??0))/1048576,2);
        }

        return view('admin/cloudflare/index', [
            'title'=>'Cloudflare Analytics — Play2TV Admin','latest'=>$latest,'totals'=>$totals,
            'cacheHitRate'=>$cacheHitRate,'snapshots'=>$snapshots,'topCountries'=>array_slice($countryTotals,0,15),
            'topBrowsers'=>array_slice($browserTotals,0,10),'subdomainTotals'=>$subdomainTotals,
            'chartLabels'=>$chartLabels,'chartPageViews'=>$chartPageViews,'chartVisitors'=>$chartVisitors,
            'chartRequests'=>$chartRequests,'chartThreats'=>$chartThreats,'chartBandwidth'=>$chartBandwidth,
        ]);
    }

    public function fetch()
    {
        if (empty(env('cloudflare.accountId'))||empty(env('cloudflare.apiToken')))
            return redirect()->back()->with('error','Cloudflare API credentials not configured in .env');

        $zones=$this->resolveZones();
        if (empty($zones)) return redirect()->back()->with('error','Geen zone IDs. Voeg cloudflare.zoneIds toe in .env.');

        $db=db_connect(); $today=date('Y-m-d'); $stored=$errors=[];
        $dateSince=date('Y-m-d',strtotime('-30 days'));
        $agg=['requests'=>0,'cached'=>0,'bandwidth'=>0,'pageViews'=>0,'threats'=>0,'bots'=>0];
        $allCountries=$allStatuses=$allBrowsers=$allSubdomains=$zoneNames=[];

        foreach ($zones as $ze) {
            $zoneId=$ze['id']??''; if(empty($zoneId)) continue;
            $zoneInfo=$this->restGet("/zones/{$zoneId}");
            $zName=$zoneInfo['result']['name']??$ze['name']??$zoneId; $zoneNames[]=$zName;

            $data=$this->fetchAnalytics($zoneId,$dateSince);
            if($data===null){$errors[]="Zone {$zName}: null";continue;}
            if(!empty($data['error'])){$errors[]="Zone {$zName}: {$data['error']}";continue;}

            $agg['requests']+=$data['requests'];$agg['cached']+=$data['cached'];
            $agg['bandwidth']+=$data['bandwidth'];$agg['pageViews']+=$data['pageViews'];
            $agg['threats']+=$data['threats'];$agg['bots']+=$data['bots'];
            foreach($data['countries'] as $c=>$n)$allCountries[$c]=($allCountries[$c]??0)+$n;
            foreach($data['statusCodes'] as $c=>$n)$allStatuses[$c]=($allStatuses[$c]??0)+$n;
            foreach($data['browsers'] as $b=>$n)$allBrowsers[$b]=($allBrowsers[$b]??0)+$n;
            $allSubdomains[$zName]=($allSubdomains[$zName]??0)+$data['requests'];
            $stored[]=$zName;
        }

        if(empty($stored)&&!empty($errors)) return redirect()->back()->with('error',implode(' | ',$errors));

        arsort($allCountries); arsort($allBrowsers);
        $existing=$db->table('cloudflare_analytics')->where('snapshot_date',$today)->get()->getRowArray();
        $row=['snapshot_date'=>$today,'zone_id'=>env('cloudflare.accountId'),'zone_name'=>implode(', ',$zoneNames),
            'total_requests'=>$agg['requests'],'cached_requests'=>$agg['cached'],
            'uncached_requests'=>$agg['requests']-$agg['cached'],'bandwidth_bytes'=>$agg['bandwidth'],
            'page_views'=>$agg['pageViews'],'unique_visitors'=>0,'threats_blocked'=>$agg['threats'],
            'threats_data'=>!empty($threatDetails)?json_encode($threatDetails):null,
            'bot_requests'=>$agg['bots'],
            'countries_data'=>!empty($allCountries)?json_encode($allCountries):null,
            'http_status_data'=>!empty($allStatuses)?json_encode($allStatuses):null,
            'browser_data'=>!empty($allBrowsers)?json_encode($allBrowsers):null,
            'subdomain_data'=>!empty($allSubdomains)?json_encode($allSubdomains):null,
            'created_at'=>date('Y-m-d H:i:s')];
        if($existing) $db->table('cloudflare_analytics')->where('id',$existing['id'])->update($row);
        else $db->table('cloudflare_analytics')->insert($row);

        $msg="Data opgehaald voor ".count($stored)." zone(s): ".implode(', ',$zoneNames).". {$agg['pageViews']} pageviews, {$agg['threats']} threats.";
        if(!empty($errors)) $msg.=' ('.implode('; ',$errors).')';
        return redirect()->to(base_url('admin/cloudflare'))->with('success',$msg);
    }

    private function fetchAnalytics(string $zoneId, string $dateSince): ?array
    {
        $q1=json_encode(['query'=>'{ viewer { zones(filter: { zoneTag: "'.$zoneId.'" }) { httpRequests1dGroups(limit: 30, filter: { date_gt: "'.$dateSince.'" }) { sum { requests pageViews threats bytes cachedRequests } dimensions { date } } } } }']);
        $r1=$this->graphqlCall($q1);
        if(!empty($r1['_error'])) return ['error'=>$r1['_error']];

        $groups=$r1['data']['viewer']['zones'][0]['httpRequests1dGroups']??[];
        $req=$pv=$threats=$bytes=$cached=0;
        foreach($groups as $g){$s=$g['sum']??[];$req+=(int)($s['requests']??0);$pv+=(int)($s['pageViews']??0);$threats+=(int)($s['threats']??0);$bytes+=(int)($s['bytes']??0);$cached+=(int)($s['cachedRequests']??0);}

        $q2=json_encode(['query'=>'{ viewer { zones(filter: { zoneTag: "'.$zoneId.'" }) { httpRequests1dGroups(limit: 1, filter: { date_gt: "'.$dateSince.'" }) { sum { countryMap { clientCountryName requests } } } } } }']);
        $r2=$this->graphqlCall($q2);
        $detailSum=[];
        if(empty($r2['_error'])) $detailSum=$r2['data']['viewer']['zones'][0]['httpRequests1dGroups'][0]['sum']??[];

        $countries=[];
        foreach($detailSum['countryMap']??[] as $item){$n=$item['clientCountryName']??'Unknown';$countries[$n]=($countries[$n]??0)+(int)($item['requests']??0);}
        arsort($countries);

        // Threat details query
        $q3=json_encode(['query'=>'{ viewer { zones(filter: { zoneTag: "'.$zoneId.'" }) { httpRequests1dGroups(limit: 7, filter: { date_gt: "'.$dateSince.'" }) { sum { threats threatPathingMap { requests threatPathingName } } dimensions { date } } } } }']);
        $r3=$this->graphqlCall($q3);
        $threatDetails=['total'=>(int)$threats,'byType'=>[],'byDay'=>[]];
        if(empty($r3['_error'])){
            $tg=$r3['data']['viewer']['zones'][0]['httpRequests1dGroups']??[];
            $typeTotals=[];
            foreach($tg as $g){
                $d=$g['dimensions']['date']??'';
                $ts=$g['sum']['threats']??0;
                $threatDetails['byDay'][$d]=(int)$ts;
                foreach($g['sum']['threatPathingMap']??[] as $tm){
                    $tn=$tm['threatPathingName']??'Unknown';
                    $typeTotals[$tn]=($typeTotals[$tn]??0)+(int)($tm['requests']??0);
                }
            }
            arsort($typeTotals);
            $threatDetails['byType']=$typeTotals;
        }

        return ['requests'=>$req,'cached'=>$cached,'bandwidth'=>$bytes,'pageViews'=>$pv,'threats'=>$threats,'bots'=>0,'countries'=>$countries,'statusCodes'=>[],'browsers'=>[],'threatDetails'=>$threatDetails];
    }

    private function resolveZones(): array
    {
        $env=env('cloudflare.zoneIds',''); if(empty($env)) return [];
        $zones=[]; foreach(array_map('trim',explode(',',$env)) as $id) if($id!=='') $zones[]=['id'=>$id,'name'=>$id];
        return $zones;
    }

    private function getAuthHeaders(): array
    {
        $email = env('cloudflare.apiEmail', '');
        $token = env('cloudflare.apiToken');
        if (!empty($email)) {
            return ['X-Auth-Email: '.$email, 'X-Auth-Key: '.$token];
        }
        return ['Authorization: Bearer '.$token];
    }

    private function graphqlCall(string $jsonBody): array
    {
        if(!function_exists('curl_init')) return ['_error'=>'PHP cURL extension not enabled'];
        $headers = $this->getAuthHeaders();
        $headers[] = 'Content-Type: application/json';

        $ch=curl_init(self::CF_GRAPHQL);
        if($ch===false) return ['_error'=>'curl_init failed'];
        curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>25,CURLOPT_POST=>true,CURLOPT_HTTPHEADER=>$headers,CURLOPT_POSTFIELDS=>$jsonBody]);
        $resp=curl_exec($ch); $httpCode=curl_getinfo($ch,CURLINFO_HTTP_CODE); $curlErr=curl_error($ch); curl_close($ch);

        if($resp===false) return ['_error'=>'cURL error: '.($curlErr?:'timeout')];
        if($httpCode>=400) return ['_error'=>"HTTP {$httpCode} — ".substr($resp,0,500)];
        $data=json_decode($resp,true);
        if(!is_array($data)) return ['_error'=>'Invalid JSON: '.substr($resp,0,200)];
        if(!empty($data['errors'])) return ['_error'=>'GraphQL errors: '.json_encode($data['errors'])];
        return $data;
    }

    private function restGet(string $path): array
    {
        if(!function_exists('curl_init')) return [];
        $headers = $this->getAuthHeaders();
        $headers[] = 'Content-Type: application/json';
        $ch=curl_init(self::CF_BASE.$path);
        if($ch===false) return [];
        curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>15,CURLOPT_HTTPHEADER=>$headers]);
        $resp=curl_exec($ch); curl_close($ch);
        if($resp===false) return [];
        $data=json_decode($resp,true);
        return is_array($data)?$data:[];
    }
}