  </section>
  <?php if(($layoutMode ?? 'two-col') !== 'one-col'): ?>
  <aside class="sidebar-col">
    <div class="content sidebar-box">
      <h3>Berita Terbaru</h3>
      <?php if(!empty($latestPosts)): ?>
        <ul class="latest-list">
          <?php foreach($latestPosts as $lp): ?>
            <li><a href="/blog/<?= e($lp['slug']) ?>"><?= e($lp['title']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p>Belum ada post terbaru.</p>
      <?php endif; ?>
    </div>
  </aside>
  <?php endif; ?>
</main>
<footer class="container"><small>© <?= date('Y') ?> Claw CMS</small></footer>
<script src="/assets/js/main.js?v=<?= file_exists(__DIR__.'/../assets/js/main.js') ? filemtime(__DIR__.'/../assets/js/main.js') : time() ?>"></script></body></html>
