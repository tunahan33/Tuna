<?php
require_once __DIR__ . '/dashboard.php';

const SERVICE_ICONS = ['bolt' => 'Şimşek (performans)', 'apple' => 'Elma (beslenme)', 'brain' => 'Beyin (mental)', 'briefcase' => 'Çanta (kariyer)',
    'shield' => 'Kalkan (kulüp)', 'stadium' => 'Stadyum (tesis)', 'megaphone' => 'Megafon (pazarlama)', 'target' => 'Hedef', 'users' => 'Takım', 'chart' => 'Grafik', 'star' => 'Yıldız'];

function admin_services(): void
{
    require_perm('catalog.edit');
    $list = rows('SELECT s.*, (SELECT COUNT(*) FROM packages p WHERE p.service_id = s.id) pc, (SELECT MIN(price) FROM packages p WHERE p.service_id = s.id AND p.is_active = 1) mp FROM services s ORDER BY sort');
    admin_render('Danışmanlıklar', function () use ($list) { ?>
<div class="page-actions">
  <?php if (can('catalog.create')): ?><a class="btn btn-primary" href="<?= url('admin/hizmet/yeni') ?>">+ Yeni danışmanlık</a><?php endif; ?>
</div>
<div class="svc-grid">
  <?php foreach ($list as $s): ?>
    <a class="card svc-card<?= $s['is_active'] ? '' : ' off' ?>" href="<?= url('admin/hizmet/' . $s['id']) ?>">
      <span class="svc-ico"><?= icon($s['icon']) ?></span>
      <div><strong><?= e($s['title']) ?></strong><small><?= (int)$s['pc'] ?> paket · <?= $s['mp'] ? money_short((int)$s['mp']) . "'den başlayan" : '—' ?></small></div>
      <?= $s['is_active'] ? '<span class="badge badge-green">Yayında</span>' : '<span class="badge badge-gray">Pasif</span>' ?>
    </a>
  <?php endforeach; ?>
</div>
<?php
    }, ['subtitle' => 'Danışmanlık türlerini düzenleyin']);
}

function admin_service_edit(string $id): void
{
    require_perm('catalog.edit');
    $isNew = $id === 'yeni';
    if ($isNew) require_perm('catalog.create');
    $s = $isNew ? ['id' => 0, 'title' => '', 'slug' => '', 'short' => '', 'description' => '', 'icon' => 'target', 'sort' => 99, 'is_active' => 1] : row('SELECT * FROM services WHERE id = ?', [(int)$id]);
    if (!$s) redirect('admin/hizmetler');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $title = post('title');
        $data = [
            'title' => $title, 'short' => post('short'), 'description' => clean_html((string)($_POST['description'] ?? '')),
            'icon' => array_key_exists(post('icon'), SERVICE_ICONS) ? post('icon') : 'target', 'sort' => (int)post('sort'),
        ];
        if (mb_strlen($title) < 3) { flash('error', 'Başlık gerekli.'); redirect('admin/hizmet/' . $id); }
        $active = can('catalog.create') ? (int)!empty($_POST['is_active']) : (int)$s['is_active'];
        if ($isNew) {
            $slug = slugify($title); $base = $slug; $n = 2;
            while (val('SELECT 1 FROM services WHERE slug = ?', [$slug])) $slug = $base . '-' . $n++;
            q('INSERT INTO services(slug, title, short, description, icon, sort, is_active) VALUES(?,?,?,?,?,?,?)', [$slug, $data['title'], $data['short'], $data['description'], $data['icon'], $data['sort'], $active]);
            $newId = db()->lastInsertId();
            log_activity('service_created', $title);
            flash('success', 'Danışmanlık eklendi. Şimdi paketlerini ekleyebilirsiniz.');
            redirect('admin/hizmet/' . $newId);
        }
        q('UPDATE services SET title = ?, short = ?, description = ?, icon = ?, sort = ?, is_active = ? WHERE id = ?', [$data['title'], $data['short'], $data['description'], $data['icon'], $data['sort'], $active, $s['id']]);
        log_activity('service_saved', $title . ($active != $s['is_active'] ? ($active ? ' (yayına aldı)' : ' (yayından kaldırdı)') : ''));
        flash('success', 'Kaydedildi.');
        redirect('admin/hizmet/' . $s['id']);
    }
    $packages = $isNew ? [] : rows('SELECT * FROM packages WHERE service_id = ? ORDER BY sort', [$s['id']]);

    admin_render($isNew ? 'Yeni danışmanlık' : $s['title'], function () use ($s, $isNew, $packages) { ?>
<div class="page-actions">
  <a class="btn btn-ghost" href="<?= url('admin/hizmetler') ?>">← Danışmanlıklar</a>
  <?php if (!$isNew): ?><a class="btn btn-ghost" target="_blank" href="<?= url('hizmet/' . $s['slug']) ?>">Sitede gör ↗</a><?php endif; ?>
</div>
<div class="grid-main">
<form method="post" class="card form" data-editor-form>
  <?= csrf_field() ?>
  <label>Başlık<input name="title" required value="<?= e($s['title']) ?>"></label>
  <label>Kısa açıklama <small>(kartlarda görünür)</small><textarea name="short" rows="2"><?= e($s['short']) ?></textarea></label>
  <label>Detaylı açıklama</label>
  <textarea name="description" data-editor rows="10"><?= e($s['description']) ?></textarea>
  <div class="grid-2">
    <label>İkon<select name="icon"><?php foreach (SERVICE_ICONS as $k => $l): ?><option value="<?= $k ?>" <?= $s['icon'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
    <label>Sıra<input type="number" name="sort" value="<?= (int)$s['sort'] ?>"></label>
  </div>
  <?php if (can('catalog.create')): ?>
    <label class="check"><input type="checkbox" name="is_active" value="1" <?= $s['is_active'] ? 'checked' : '' ?>> <span>Sitede yayında</span></label>
  <?php endif; ?>
  <button class="btn btn-primary">Kaydet</button>
</form>
<?php if (!$isNew): ?>
<section class="card">
  <div class="card-head"><h3>Paketler</h3><?php if (can('catalog.create')): ?><a class="btn btn-sm btn-dark" href="<?= url('admin/paket/yeni?hizmet=' . $s['id']) ?>">+ Paket ekle</a><?php endif; ?></div>
  <ul class="simple-list">
    <?php foreach ($packages as $p): ?>
      <li><a href="<?= url('admin/paket/' . $p['id']) ?>"><?= e($p['name']) ?> <?= $p['is_active'] ? '' : '<span class="badge badge-gray">Pasif</span>' ?></a><b><?= money((int)$p['price']) ?></b></li>
    <?php endforeach; ?>
  </ul>
</section>
<?php endif; ?>
</div>
<?php
    });
}

function admin_packages(): void
{
    require_perm('catalog.edit');
    $list = rows('SELECT p.*, s.title service_title FROM packages p JOIN services s ON s.id = p.service_id ORDER BY s.sort, p.sort');
    admin_render('Paketler & Fiyatlar', function () use ($list) { ?>
<div class="page-actions"><?php if (can('catalog.create')): ?><a class="btn btn-primary" href="<?= url('admin/paket/yeni') ?>">+ Yeni paket</a><?php endif; ?></div>
<?php if (!can('catalog.price')): ?><div class="alert alert-info">Editör rolüyle paket içeriklerini düzenleyebilirsiniz; fiyat değişikliği Admin / Süper Admin yetkisindedir.</div><?php endif; ?>
<section class="card"><div class="table-wrap"><table class="table">
  <thead><tr><th>Danışmanlık</th><th>Paket</th><th>Süre</th><th class="r">Fiyat (KDV dahil)</th><th>Durum</th></tr></thead>
  <tbody><?php foreach ($list as $p): ?>
    <tr class="clickable" data-href="<?= url('admin/paket/' . $p['id']) ?>">
      <td><?= e($p['service_title']) ?></td>
      <td><a href="<?= url('admin/paket/' . $p['id']) ?>"><strong><?= e($p['name']) ?></strong></a> <?= $p['is_featured'] ? '<span class="badge badge-yellow">Öne çıkan</span>' : '' ?></td>
      <td><?= e($p['duration']) ?></td>
      <td class="r"><strong><?= money((int)$p['price']) ?></strong></td>
      <td><?= $p['is_active'] ? '<span class="badge badge-green">Yayında</span>' : '<span class="badge badge-gray">Pasif</span>' ?></td>
    </tr>
  <?php endforeach; ?></tbody>
</table></div></section>
<?php
    });
}

function admin_package_edit(string $id): void
{
    require_perm('catalog.edit');
    $isNew = $id === 'yeni';
    if ($isNew) require_perm('catalog.create');
    $p = $isNew ? ['id' => 0, 'service_id' => (int)($_GET['hizmet'] ?? 0), 'name' => '', 'price' => 0, 'duration' => '1 ay', 'short' => '', 'description' => '', 'features' => '', 'is_featured' => 0, 'sort' => 9, 'is_active' => 1]
        : row('SELECT * FROM packages WHERE id = ?', [(int)$id]);
    if (!$p) redirect('admin/paketler');
    $services = rows('SELECT id, title FROM services ORDER BY sort');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $name = post('name');
        $price = can('catalog.price') ? parse_price(post('price')) : (int)$p['price'];
        $serviceId = can('catalog.create') ? (int)post('service_id') : (int)$p['service_id'];
        $active = can('catalog.create') ? (int)!empty($_POST['is_active']) : (int)$p['is_active'];
        $featured = (int)!empty($_POST['is_featured']);
        $features = implode("\n", array_filter(array_map('trim', explode("\n", (string)($_POST['features'] ?? '')))));
        if (mb_strlen($name) < 2 || $price < 100 || !val('SELECT 1 FROM services WHERE id = ?', [$serviceId])) {
            flash('error', 'Paket adı, geçerli bir fiyat (min. 1 ₺) ve danışmanlık seçimi gerekli.');
            redirect('admin/paket/' . $id . ($isNew ? '?hizmet=' . $serviceId : ''));
        }
        $vals = [$serviceId, $name, $price, post('duration'), post('short'), clean_html((string)($_POST['description'] ?? '')), $features, $featured, (int)post('sort'), $active];
        if ($isNew) {
            q('INSERT INTO packages(service_id, name, price, duration, short, description, features, is_featured, sort, is_active) VALUES(?,?,?,?,?,?,?,?,?,?)', $vals);
            $newId = db()->lastInsertId();
            log_activity('package_created', $name . ' — ' . money($price));
            flash('success', 'Paket eklendi.');
            redirect('admin/paket/' . $newId);
        }
        q('UPDATE packages SET service_id=?, name=?, price=?, duration=?, short=?, description=?, features=?, is_featured=?, sort=?, is_active=? WHERE id = ?', array_merge($vals, [$p['id']]));
        if ($price !== (int)$p['price']) log_activity('price_changed', $name . ': ' . money((int)$p['price']) . ' → ' . money($price));
        log_activity('package_saved', $name);
        flash('success', 'Paket kaydedildi.');
        redirect('admin/paket/' . $p['id']);
    }

    admin_render($isNew ? 'Yeni paket' : 'Paket: ' . $p['name'], function () use ($p, $services, $isNew) { ?>
<div class="page-actions"><a class="btn btn-ghost" href="<?= url('admin/paketler') ?>">← Paketler</a><?php if (!$isNew): ?><a class="btn btn-ghost" target="_blank" href="<?= url('paket/' . $p['id']) ?>">Sitede gör ↗</a><?php endif; ?></div>
<form method="post" class="card form" data-editor-form>
  <?= csrf_field() ?>
  <div class="grid-3">
    <label>Danışmanlık<select name="service_id" <?= can('catalog.create') ? '' : 'disabled' ?>><?php foreach ($services as $s): ?><option value="<?= $s['id'] ?>" <?= (int)$p['service_id'] === (int)$s['id'] ? 'selected' : '' ?>><?= e($s['title']) ?></option><?php endforeach; ?></select></label>
    <label>Paket adı<input name="name" required value="<?= e($p['name']) ?>"></label>
    <label>Süre<input name="duration" value="<?= e($p['duration']) ?>" placeholder="1 ay, 3 ay, 6 hafta…"></label>
  </div>
  <div class="grid-3">
    <label>Fiyat (₺, KDV dahil)
      <input name="price" inputmode="decimal" value="<?= number_format($p['price'] / 100, 2, ',', '.') ?>" <?= can('catalog.price') ? '' : 'readonly title="Fiyat değişikliği Admin yetkisindedir"' ?>>
      <?php if (!can('catalog.price')): ?><small class="muted">🔒 Fiyat yalnızca Admin/Süper Admin tarafından değiştirilebilir</small><?php endif; ?>
    </label>
    <label>Sıra<input type="number" name="sort" value="<?= (int)$p['sort'] ?>"></label>
    <div class="checks">
      <label class="check"><input type="checkbox" name="is_featured" value="1" <?= $p['is_featured'] ? 'checked' : '' ?>> <span>Öne çıkan paket</span></label>
      <?php if (can('catalog.create')): ?><label class="check"><input type="checkbox" name="is_active" value="1" <?= $p['is_active'] ? 'checked' : '' ?>> <span>Sitede yayında</span></label><?php endif; ?>
    </div>
  </div>
  <label>Kısa açıklama<textarea name="short" rows="2"><?= e($p['short']) ?></textarea></label>
  <label>Detaylı açıklama <small>(pakete tıklanınca açılan pencerede görünür)</small></label>
  <textarea name="description" data-editor rows="8"><?= e($p['description']) ?></textarea>
  <label>Paket içeriği <small>(her satıra bir madde)</small><textarea name="features" rows="7"><?= e($p['features']) ?></textarea></label>
  <button class="btn btn-primary">Kaydet</button>
</form>
<?php
    });
}

function admin_pages(): void
{
    require_perm('pages.edit');
    $list = rows('SELECT slug, title, updated_at, updated_by FROM pages ORDER BY title');
    admin_render('Sayfalar & Sözleşmeler', function () use ($list) { ?>
<div class="alert alert-info">Metinlerde <code>{{unvan}}</code>, <code>{{adres}}</code>, <code>{{vergi_no}}</code>, <code>{{mersis}}</code>, <code>{{telefon}}</code>, <code>{{eposta}}</code> gibi alanlar Ayarlar'daki şirket bilgileriyle otomatik doldurulur.</div>
<section class="card"><div class="table-wrap"><table class="table">
  <thead><tr><th>Sayfa</th><th>Adres</th><th>Son güncelleme</th><th></th></tr></thead>
  <tbody><?php foreach ($list as $pg): ?>
    <tr class="clickable" data-href="<?= url('admin/sayfa/' . $pg['slug']) ?>">
      <td><strong><?= e($pg['title']) ?></strong></td><td><code>/<?= e($pg['slug']) ?></code></td>
      <td><?= tr_date($pg['updated_at']) ?> <small class="muted"><?= e($pg['updated_by']) ?></small></td>
      <td class="r"><a class="btn btn-sm btn-ghost" href="<?= url('admin/sayfa/' . $pg['slug']) ?>">Düzenle</a></td>
    </tr>
  <?php endforeach; ?></tbody>
</table></div></section>
<?php
    });
}

function admin_page_edit(string $slug): void
{
    $u = require_perm('pages.edit');
    $pg = page($slug);
    if (!$pg) redirect('admin/sayfalar');
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        q('UPDATE pages SET title = ?, content = ?, updated_at = ?, updated_by = ? WHERE slug = ?',
            [post('title') ?: $pg['title'], clean_html((string)($_POST['content'] ?? '')), now(), $u['name'], $slug]);
        log_activity('page_saved', $pg['title']);
        flash('success', 'Sayfa kaydedildi.');
        redirect('admin/sayfa/' . $slug);
    }
    admin_render('Sayfa: ' . $pg['title'], function () use ($pg) { ?>
<div class="page-actions"><a class="btn btn-ghost" href="<?= url('admin/sayfalar') ?>">← Sayfalar</a><a class="btn btn-ghost" target="_blank" href="<?= url($pg['slug']) ?>">Sitede gör ↗</a></div>
<form method="post" class="card form" data-editor-form>
  <?= csrf_field() ?>
  <label>Başlık<input name="title" value="<?= e($pg['title']) ?>"></label>
  <label>İçerik</label>
  <textarea name="content" data-editor rows="20"><?= e($pg['content']) ?></textarea>
  <button class="btn btn-primary">Kaydet</button>
</form>
<?php
    }, ['subtitle' => 'Son güncelleme: ' . tr_date($pg['updated_at']) . ' · ' . $pg['updated_by']]);
}
