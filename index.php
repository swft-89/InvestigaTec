<?php require __DIR__.'/includes/bootstrap.php'; ?>
<!doctype html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="<?= e(csrf_token()) ?>"><title><?= e($labels[$page]) ?> · InvestigaTec</title><link rel="stylesheet" href="assets/css/styles.css"></head>
<body data-page="<?= e($page) ?>"><?php require __DIR__.'/includes/sidebar.php'; ?>
<div class="workspace"><?php require __DIR__.'/includes/header.php'; ?><main id="contenido" class="content"><?php require __DIR__.'/pages/'.$page.'.php'; ?></main><footer class="footer">InvestigaTec · Repositorio Digital de Proyectos de Investigación <span>Instituto Tecnológico de Ciudad Juárez</span></footer></div>
<div class="toast" id="toast" role="status" aria-live="polite"></div>
<script>window.INVESTIGATEC_AUTH_USER = <?= json_encode(current_user(), JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_INVALID_UTF8_SUBSTITUTE) ?>;</script>
<script type="module" src="assets/js/main.js"></script></body></html>
