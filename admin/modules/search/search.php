<?php
/**
 * Global Search Page - Admin Panel
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';

$pageTitle = "Pencarian Global";
$currentPage = "search";

$db = Database::getInstance()->getConnection();

// Get search query and filter
$q = trim($_GET['q'] ?? '');
$type = $_GET['type'] ?? ''; // module filter
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Available modules
$modules = [
    'posts' => 'Posts',
    'services' => 'Layanan',
    'users' => 'Users',
    'files' => 'File Download',
    'albums' => 'Album Gallery',
    'photos' => 'Foto Gallery',
    'banners' => 'Banner',
    'contacts' => 'Pesan Kontak'
];

// Validate type filter
if ($type && !array_key_exists($type, $modules)) {
    $type = '';
}

$results = [];
$totalResults = 0;

// Function to highlight query term in result snippet
function highlight($text, $query) {
    $escapedQuery = preg_quote($query, '/');
    return preg_replace_callback("/($escapedQuery)/i", function ($matches) {
        return '<mark>' . $matches[0] . '</mark>';
    }, $text);
}

// Search logic for each module
if ($q) {
    if (!$type || $type === 'posts') {
        $sql = "SELECT id, title, LEFT(content, 200) as snippet FROM posts WHERE (title LIKE ? OR content LIKE ?) AND deleted_at IS NULL";
        $stmt = $db->prepare($sql);
        $param = "%{$q}%";
        $stmt->execute([$param, $param]);
        $posts = $stmt->fetchAll();
        foreach ($posts as $p) {
            $results[] = [
                'id' => $p['id'],
                'type' => 'posts',
                'type_label' => 'Post',
                'title' => highlight($p['title'], $q),
                'snippet' => highlight($p['snippet'], $q),
                'url' => ADMIN_URL . "modules/posts/posts_edit.php?id=" . $p['id'],
            ];
        }
    }

    if (!$type || $type === 'services') {
        $sql = "SELECT id, title, LEFT(description, 200) as snippet FROM services WHERE (title LIKE ? OR description LIKE ?) AND deleted_at IS NULL";
        $stmt = $db->prepare($sql);
        $param = "%{$q}%";
        $stmt->execute([$param, $param]);
        $services = $stmt->fetchAll();
        foreach ($services as $s) {
            $results[] = [
                'id' => $s['id'],
                'type' => 'services',
                'type_label' => 'Layanan',
                'title' => highlight($s['title'], $q),
                'snippet' => highlight($s['snippet'], $q),
                'url' => ADMIN_URL . "modules/services/services_edit.php?id=" . $s['id'],
            ];
        }
    }

    if (!$type || $type === 'users') {
        $sql = "SELECT id, name, email FROM users WHERE (name LIKE ? OR email LIKE ?) AND deleted_at IS NULL";
        $stmt = $db->prepare($sql);
        $param = "%{$q}%";
        $stmt->execute([$param, $param]);
        $users = $stmt->fetchAll();
        foreach ($users as $u) {
            $results[] = [
                'id' => $u['id'],
                'type' => 'users',
                'type_label' => 'User',
                'title' => highlight($u['name'], $q),
                'snippet' => htmlspecialchars($u['email']),
                'url' => ADMIN_URL . "modules/users/users_edit.php?id=" . $u['id'],
            ];
        }
    }

    if (!$type || $type === 'files') {
        $sql = "SELECT id, title, LEFT(description, 200) as snippet FROM downloadable_files WHERE (title LIKE ? OR description LIKE ?) AND deleted_at IS NULL";
        $stmt = $db->prepare($sql);
        $param = "%{$q}%";
        $stmt->execute([$param, $param]);
        $files = $stmt->fetchAll();
        foreach ($files as $f) {
            $results[] = [
                'id' => $f['id'],
                'type' => 'files',
                'type_label' => 'File Download',
                'title' => highlight($f['title'], $q),
                'snippet' => highlight($f['snippet'], $q),
                'url' => ADMIN_URL . "modules/files/files_edit.php?id=" . $f['id'],
            ];
        }
    }

    if (!$type || $type === 'albums') {
        $sql = "SELECT id, name, LEFT(description, 200) as snippet FROM gallery_albums WHERE (name LIKE ? OR description LIKE ?) AND deleted_at IS NULL";
        $stmt = $db->prepare($sql);
        $param = "%{$q}%";
        $stmt->execute([$param, $param]);
        $albums = $stmt->fetchAll();
        foreach ($albums as $a) {
            $results[] = [
                'id' => $a['id'],
                'type' => 'albums',
                'type_label' => 'Album Gallery',
                'title' => highlight($a['name'], $q),
                'snippet' => highlight($a['snippet'], $q),
                'url' => ADMIN_URL . "modules/gallery/albums_edit.php?id=" . $a['id'],
            ];
        }
    }

    if (!$type || $type === 'photos') {
        $sql = "SELECT p.id, COALESCE(p.title, CONCAT('Photo #', p.id)) as title, LEFT(p.caption, 200) as snippet FROM gallery_photos p WHERE (p.title LIKE ? OR p.caption LIKE ?) AND p.deleted_at IS NULL";
        $stmt = $db->prepare($sql);
        $param = "%{$q}%";
        $stmt->execute([$param, $param]);
        $photos = $stmt->fetchAll();
        foreach ($photos as $ph) {
            $results[] = [
                'id' => $ph['id'],
                'type' => 'photos',
                'type_label' => 'Foto Gallery',
                'title' => highlight($ph['title'], $q),
                'snippet' => highlight($ph['snippet'], $q),
                'url' => ADMIN_URL . "modules/gallery/photos_edit.php?id=" . $ph['id'],
            ];
        }
    }

    // Add search for banners
    if (!$type || $type === 'banners') {
        $sql = "SELECT id, title, LEFT(caption, 200) as snippet FROM banners WHERE (title LIKE ? OR caption LIKE ?) AND deleted_at IS NULL";
        $stmt = $db->prepare($sql);
        $param = "%{$q}%";
        $stmt->execute([$param, $param]);
        $banners = $stmt->fetchAll();
        foreach ($banners as $b) {
            $results[] = [
                'id' => $b['id'],
                'type' => 'banners',
                'type_label' => 'Banner',
                'title' => highlight($b['title'], $q),
                'snippet' => highlight($b['snippet'], $q),
                'url' => ADMIN_URL . "modules/banners/banners_edit.php?id=" . $b['id'],
            ];
        }
    }

    // Add search for contact messages
    if (!$type || $type === 'contacts') {
        $sql = "SELECT id, subject as title, LEFT(message, 200) as snippet FROM contact_messages WHERE (subject LIKE ? OR message LIKE ?) AND deleted_at IS NULL";
        $stmt = $db->prepare($sql);
        $param = "%{$q}%";
        $stmt->execute([$param, $param]);
        $contacts = $stmt->fetchAll();
        foreach ($contacts as $c) {
            $results[] = [
                'id' => $c['id'],
                'type' => 'contacts',
                'type_label' => 'Pesan Kontak',
                'title' => highlight($c['title'], $q),
                'snippet' => highlight($c['snippet'], $q),
                'url' => ADMIN_URL . "modules/contact/messages_view.php?id=" . $c['id'],
            ];
        }
    }
}

$totalResults = count($results);

// Pagination for results
$paginatedResults = array_slice($results, $offset, $perPage);
$totalPages = ceil($totalResults / $perPage);

include '../../includes/header.php';
?>

<div class="page-heading">
    <div class="page-title">
        <div class="row align-items-center">
            <div class="col-12">
                <h3><i class=""></i><?= $pageTitle ?></h3>
                <p class="text-subtitle text-muted mb-0">
                    <?php if ($q): ?>
                        Search results for: <strong><?= htmlspecialchars($q) ?></strong>
                    <?php else: ?>
                        Enter keywords to search across all modules
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" id="search-form">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <div class="position-relative">
                                <label for="search-input" class="form-label">Search Keywords</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-search"></i>
                                    </span>
                                    <input type="text" name="q" id="search-input" class="form-control" placeholder="Enter keywords..." autocomplete="off" value="<?= htmlspecialchars($q) ?>">
                                </div>
                                <ul id="search-suggestions" class="list-group position-absolute w-100" style="z-index: 1050; max-height: 300px; overflow-y: auto; display: none;"></ul>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="type-filter" class="form-label">Filter by Module</label>
                            <select name="type" id="type-filter" class="form-select">
                                <option value="">All Modules</option>
                                <?php foreach ($modules as $key => $label): ?>
                                    <option value="<?= $key ?>" <?= $key === $type ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-search me-1"></i> Search
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($q): ?>
            <?php if (empty($results)): ?>
                <div class="card shadow-sm">
                    <div class="card-body text-center py-5">
                        <div class="mb-3">
                            <i class="bi bi-search text-muted" style="font-size: 3rem;"></i>
                        </div>
                        <h5 class="text-muted">No results found</h5>
                        <p class="text-muted">No results found for <strong><?= htmlspecialchars($q) ?></strong>. Try different keywords or filters.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="card shadow-sm">
                    <div class="card-header border-bottom">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Search Results</h5>
                            <span class="badge bg-primary"><?= $totalResults ?> results found</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive d-none d-md-block">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Snippet</th>
                                        <th>Type</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($paginatedResults as $item): ?>
                                        <tr>
                                            <td>
                                                <h6 class="mb-0"><?= $item['title'] ?></h6>
                                            </td>
                                            <td>
                                                <p class="text-muted mb-0"><?= $item['snippet'] ?>...</p>
                                            </td>
                                            <td>
                                                <?php
                                                $typeColors = [
                                                    'posts' => 'primary',
                                                    'services' => 'success',
                                                    'users' => 'info',
                                                    'files' => 'warning',
                                                    'albums' => 'secondary',
                                                    'photos' => 'dark'
                                                ];
                                                $color = $typeColors[$item['type']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?= $color ?>"><?= $item['type_label'] ?></span>
                                            </td>
                                            <td class="text-center">
                                                <a href="<?= $item['url'] ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                                    <i class="bi bi-box-arrow-up-right"></i> Open
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="d-md-none p-3">
                            <?php foreach ($paginatedResults as $item): ?>
                                <div class="card mb-3 shadow-sm">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="mb-0"><?= $item['title'] ?></h6>
                                            <?php
                                            $typeColors = [
                                                'posts' => 'primary',
                                                'services' => 'success',
                                                'users' => 'info',
                                                'files' => 'warning',
                                                'albums' => 'secondary',
                                                'photos' => 'dark'
                                            ];
                                            $color = $typeColors[$item['type']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?= $color ?>"><?= $item['type_label'] ?></span>
                                        </div>
                                        <p class="text-muted mb-3"><?= $item['snippet'] ?>...</p>
                                        <div class="d-flex justify-content-end">
                                            <a href="<?= $item['url'] ?>" class="btn btn-sm btn-primary" target="_blank">
                                                <i class="bi bi-box-arrow-up-right me-1"></i> Open
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php if ($totalPages > 1): ?>
                        <div class="card-footer border-top">
                            <nav aria-label="Page navigation">
                                <ul class="pagination pagination-sm justify-content-center mb-0">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?q=<?= urlencode($q) ?>&type=<?= urlencode($type) ?>&page=<?= $page-1 ?>">
                                                <i class="bi bi-chevron-left"></i>
                                                <span class="d-none d-md-inline ms-1">Previous</span>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php
                                    $startPage = max(1, $page - 2);
                                    $endPage = min($totalPages, $page + 2);
                                    for ($i = $startPage; $i <= $endPage; $i++):
                                    ?>
                                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                            <a class="page-link" href="?q=<?= urlencode($q) ?>&type=<?= urlencode($type) ?>&page=<?= $i ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?q=<?= urlencode($q) ?>&type=<?= urlencode($type) ?>&page=<?= $page+1 ?>">
                                                <span class="d-none d-md-inline me-1">Next</span>
                                                <i class="bi bi-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="card shadow-sm">
                <div class="card-body text-center py-5">
                    <div class="mb-3">
                        <i class="bi bi-search text-muted" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="text-muted">Start Your Search</h5>
                    <p class="text-muted">Enter keywords in the search form above to find content across all modules.</p>
                    
                    <div class="row mt-4">
                        <div class="col-md-8 mx-auto">
                            <div class="card border-0">
                                <div class="card-body">
                                    <h6 class="card-title">Search Tips</h6>
                                    <ul class="list-unstyled text-start">
                                        <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i> Use specific keywords for better results</li>
                                        <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i> Filter by module to narrow down your search</li>
                                        <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i> Search is not case sensitive</li>
                                        <li><i class="bi bi-check-circle text-success me-2"></i> Partial matches will be included in results</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search-input');
    const suggestionsBox = document.getElementById('search-suggestions');
    const searchForm = document.getElementById('search-form');
    const typeFilter = document.getElementById('type-filter');
    let debounceTimer = null;

    // Auto-submit form when filter changes
    typeFilter.addEventListener('change', function() {
        searchForm.submit();
    });

    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        const query = searchInput.value.trim();
        if (query.length < 2) {
            suggestionsBox.style.display = 'none';
            suggestionsBox.innerHTML = '';
            return;
        }
        debounceTimer = setTimeout(() => {
            fetch(`ajax_search.php?q=${encodeURIComponent(query)}&type=<?= $type ?>`)
                .then(response => response.json())
                .then(data => {
                    suggestionsBox.innerHTML = '';
                    if (data.length === 0) {
                        suggestionsBox.style.display = 'none';
                        return;
                    }
                    data.forEach(item => {
                        const li = document.createElement('li');
                        li.classList.add('list-group-item', 'list-group-item-action');
                        li.innerHTML = item.label;
                        li.style.cursor = 'pointer';
                        li.onclick = () => {
                            window.location.href = item.url;
                        };
                        suggestionsBox.appendChild(li);
                    });
                    suggestionsBox.style.display = 'block';
                })
                .catch(() => {
                    suggestionsBox.style.display = 'none';
                    suggestionsBox.innerHTML = '';
                });
        }, 300);
    });

    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
            suggestionsBox.style.display = 'none';
            suggestionsBox.innerHTML = '';
        }
    });
});
</script>

<?php include '../../includes/footer.php'; ?>