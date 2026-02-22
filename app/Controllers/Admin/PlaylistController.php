<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\PlaylistModel;
use CodeIgniter\Controller;

/**
 * PlaylistController (Admin)
 *
 * Manages M3U playlists that are served to premium API users.
 *
 * Routes (all behind /admin prefix, protected by AdminAuthFilter):
 *   GET  /admin/playlists           → List all playlists
 *   GET  /admin/playlists/add       → Upload form
 *   POST /admin/playlists/add       → Save uploaded playlist
 *   GET  /admin/playlists/{id}/edit → Edit form
 *   POST /admin/playlists/{id}/edit → Save edits
 *   GET  /admin/playlists/{id}/activate → Set as active
 *   GET  /admin/playlists/{id}/delete   → Delete playlist
 */
class PlaylistController extends Controller
{
    private PlaylistModel $playlistModel;

    public function __construct()
    {
        $this->playlistModel = new PlaylistModel();
        helper(['url', 'form']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /admin/playlists
    // ─────────────────────────────────────────────────────────────────────────
    public function index()
    {
        $playlists = $this->playlistModel
            ->select('id, name, is_active, created_at, updated_at, CHAR_LENGTH(m3u_content) AS content_size')
            ->orderBy('created_at', 'DESC')
            ->findAll();

        return view('admin/playlists/index', [
            'title'     => 'Playlists — Play2TV Admin',
            'playlists' => $playlists,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /admin/playlists/add
    // ─────────────────────────────────────────────────────────────────────────
    public function addForm()
    {
        return view('admin/playlists/add', [
            'title' => 'Playlist toevoegen — Play2TV Admin',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /admin/playlists/add
    // Accepts either file upload or pasted M3U content
    // ─────────────────────────────────────────────────────────────────────────
    public function add()
    {
        $name    = trim($this->request->getPost('name') ?? '');
        $content = null;

        if (empty($name)) {
            return redirect()->back()->with('error', 'Playlist naam is verplicht.');
        }

        // Option 1: file upload
        $file = $this->request->getFile('m3u_file');
        if ($file && $file->isValid() && ! $file->hasMoved()) {
            $content = file_get_contents($file->getTempName());
        }

        // Option 2: pasted content
        if (empty($content)) {
            $content = $this->request->getPost('m3u_content') ?? '';
        }

        if (empty($content)) {
            return redirect()->back()->with('error', 'Upload een M3U bestand of plak de inhoud.');
        }

        // Validate M3U starts correctly
        if (! str_starts_with(ltrim($content), '#EXTM3U')) {
            return redirect()->back()->with('error', 'Ongeldig M3U formaat. Bestand moet beginnen met #EXTM3U');
        }

        $id = $this->playlistModel->insert([
            'name'        => $name,
            'm3u_content' => $content,
            'is_active'   => 0,
        ]);

        if (! $id) {
            return redirect()->back()->with('error', 'Opslaan mislukt.');
        }

        return redirect()->to(base_url('admin/playlists'))->with('success', 'Playlist opgeslagen (ID: ' . $id . ').');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /admin/playlists/{id}/edit
    // ─────────────────────────────────────────────────────────────────────────
    public function editForm($id)
    {
        $playlist = $this->playlistModel->find($id);

        if (! $playlist) {
            return redirect()->to(base_url('admin/playlists'))->with('error', 'Playlist niet gevonden.');
        }

        return view('admin/playlists/edit', [
            'title'    => 'Playlist bewerken — Play2TV Admin',
            'playlist' => $playlist,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /admin/playlists/{id}/edit
    // ─────────────────────────────────────────────────────────────────────────
    public function edit($id)
    {
        $playlist = $this->playlistModel->find($id);

        if (! $playlist) {
            return redirect()->to(base_url('admin/playlists'))->with('error', 'Playlist niet gevonden.');
        }

        $name    = trim($this->request->getPost('name') ?? $playlist['name']);
        $content = $this->request->getPost('m3u_content') ?? $playlist['m3u_content'];

        // Allow file re-upload
        $file = $this->request->getFile('m3u_file');
        if ($file && $file->isValid() && ! $file->hasMoved()) {
            $content = file_get_contents($file->getTempName());
        }

        if (! str_starts_with(ltrim($content), '#EXTM3U')) {
            return redirect()->back()->with('error', 'Ongeldig M3U formaat.');
        }

        $this->playlistModel->update($id, [
            'name'        => $name,
            'm3u_content' => $content,
        ]);

        return redirect()->to(base_url('admin/playlists'))->with('success', 'Playlist bijgewerkt.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /admin/playlists/{id}/activate
    // ─────────────────────────────────────────────────────────────────────────
    public function activate($id)
    {
        $playlist = $this->playlistModel->find($id);

        if (! $playlist) {
            return redirect()->to(base_url('admin/playlists'))->with('error', 'Playlist niet gevonden.');
        }

        $this->playlistModel->setActivePlaylist((int) $id);

        return redirect()->to(base_url('admin/playlists'))->with('success', '"' . $playlist['name'] . '" is nu de actieve playlist.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /admin/playlists/{id}/delete
    // ─────────────────────────────────────────────────────────────────────────
    public function delete($id)
    {
        $playlist = $this->playlistModel->find($id);

        if (! $playlist) {
            return redirect()->to(base_url('admin/playlists'))->with('error', 'Playlist niet gevonden.');
        }

        $this->playlistModel->delete($id);

        return redirect()->to(base_url('admin/playlists'))->with('success', 'Playlist verwijderd.');
    }
}
