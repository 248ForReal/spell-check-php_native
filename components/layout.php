<div class="">
  <?php include './components/header.php' ?>
  <main class="p-8">
    <?php
    if (array_key_exists($page, $pages)) {
      if (is_array($pages[$page]) && array_key_exists($action, $pages[$page])) {
        require_once $pages[$page][$action];
      } else {
        require_once $pages[$page];
      }
    } else {
      require_once 'form-data.php';
    }
    ?>
  </main>
</div>