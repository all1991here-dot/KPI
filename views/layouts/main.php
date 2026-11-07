<?php
use yii\helpers\{Html,Url};
use app\services\Ui;
use app\models\User;
$user=Yii::$app->user->identity;
$route=$this->context->route;
$mine=(bool)Yii::$app->request->get('mine');$queue=(bool)Yii::$app->request->get('queue');$appeals=(bool)Yii::$app->request->get('appeals');$isApplications=$route==='kpi/applications';
$navigation=[['kpi/index','Boshqaruv paneli','grid'],['kpi/create','Yangi faoliyat','plus'],['kpi/criteria','KPI mezonlari','book'],['kpi/reports','Reyting va hisobot','chart'],['kpi/notifications','Bildirishnomalar','bell'],['kpi/profile','Shaxsiy profil','user']];
$adminNavigation=[['users','Foydalanuvchilar','users'],['structure','Universitet tuzilmasi','building'],['periods','Baholash davrlari','calendar'],['rules','Hisoblash qoidalari','settings'],['route','Tasdiqlash bosqichlari','route'],['hemis','HEMIS integratsiyasi','link'],['audit','Amallar jurnali','history']];
if($user && $user->role==='superadmin')$adminNavigation[]=['indicators','Mezonlarni boshqarish','book'];
$pageTitle=str_replace([' · KPI',' · FarDU KPI'],'',$this->title?:'Boshqaruv paneli');
$assetVersion='3.2.0';
?>
<?php $this->beginPage() ?>
<!doctype html><html lang="uz" data-theme="<?= $user?'dark':'light' ?>" data-skin="<?= $user?'galaxy':'auth' ?>"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="theme-color" content="#111b35">
<title><?= Ui::h($this->title?:'KPI · Universitet') ?></title><?= Html::csrfMetaTags() ?>
<link rel="stylesheet" href="<?= Ui::h(Yii::$app->request->baseUrl) ?>/css/app.css?v=<?= $assetVersion ?>">
<link rel="stylesheet" href="<?= Ui::h(Yii::$app->request->baseUrl) ?>/css/design.css?v=<?= $assetVersion ?>">
<link rel="stylesheet" href="<?= Ui::h(Yii::$app->request->baseUrl) ?>/css/academic.css?v=<?= $assetVersion ?>">
<?php if($user): ?><link rel="stylesheet" href="<?= Ui::h(Yii::$app->request->baseUrl) ?>/css/galaxy.css?v=<?= $assetVersion ?>"><?php endif ?>
<script src="<?= Ui::h(Yii::$app->request->baseUrl) ?>/js/app.js?v=<?= $assetVersion ?>" defer></script>
<?php $this->head() ?></head><body class="<?= $user?'academic-app galaxy-app':'academic-auth' ?>"><?php $this->beginBody() ?>
<?php if($user): ?>
<a class="skip-link" href="#main">Asosiy qismga o‘tish</a>
<aside class="sidebar" id="app-sidebar" aria-label="Asosiy menyu">
<div class="sidebar-brand"><a class="brand" href="<?= Ui::h(Url::to(['kpi/index'])) ?>"><span class="academic-sidebar-logo"><?= Ui::brandLogo() ?></span><span>FarDU<span class="brand-sub">KPI BAHOLASH TIZIMI</span></span></a><button class="icon-button sidebar-close" type="button" data-menu-close aria-label="Menyuni yopish"><?= Ui::icon('close') ?></button></div>
<div class="organization-mark"><?= Ui::icon('building') ?><span>Farg‘ona davlat universiteti<small>Faoliyat va natijalar boshqaruvi</small></span></div>
<nav class="sidebar-nav" aria-label="Sahifalar"><div class="nav-label">ASOSIY MENYU</div>
<?php foreach($navigation as [$url,$label,$icon]): $active=$route===$url && !($url==='kpi/applications' && $queue); ?>
<?= Html::a(Ui::icon($icon).'<span>'.Ui::h($label).'</span>',[$url],['class'=>$active?'nav-link active':'nav-link','aria-current'=>$active?'page':null]) ?>
<?php if($url==='kpi/index'): ?>
<div class="application-nav-group"><button type="button" class="nav-link nav-group-toggle" data-nav-group-toggle aria-controls="application-subnav" aria-expanded="<?= $isApplications?'true':'false' ?>"><?= Ui::icon('file') ?><span>Arizalar</span><span class="nav-chevron" aria-hidden="true">⌄</span></button>
<div id="application-subnav" class="nav-submenu"<?= $isApplications?'':' hidden' ?>>
<?php if($user->canReview()):$active=$isApplications && !$mine && !$queue && !$appeals; ?><?= Html::a('Barcha arizalar',['kpi/applications'],['class'=>$active?'nav-link active':'nav-link','aria-current'=>$active?'page':null]) ?><?php endif ?>
<?php $active=$isApplications && !$queue && !$appeals && ($mine || !$user->canReview()); ?><?= Html::a('Mening arizalarim',['kpi/applications','mine'=>1],['class'=>$active?'nav-link active':'nav-link','aria-current'=>$active?'page':null]) ?>
<?php if($user->canReview()):$active=$isApplications && $queue; ?><?= Html::a('Tekshiruv navbati',['kpi/applications','queue'=>1],['class'=>$active?'nav-link active':'nav-link','aria-current'=>$active?'page':null]) ?><?php endif ?>
<?php $active=$isApplications && $appeals && !$queue; ?><?= Html::a('Apellyatsiyalar',['kpi/applications','appeals'=>1],['class'=>$active?'nav-link active':'nav-link','aria-current'=>$active?'page':null]) ?>
</div></div><?php endif ?>
<?php endforeach ?>
<?php if($user->isAdmin()): ?><div class="nav-label">ADMINISTRATOR</div><?php foreach($adminNavigation as [$url,$label,$icon]): $active=$route==='admin/'.$url; ?><?= Html::a(Ui::icon($icon).'<span>'.Ui::h($label).'</span>',['admin/'.$url],['class'=>$active?'nav-link active':'nav-link','aria-current'=>$active?'page':null]) ?><?php endforeach ?><?php endif ?>
</nav>
<div class="sidebar-bottom"><div class="mobile-signout"><?= Html::beginForm(['auth/logout'],'post') ?><button class="button secondary" type="submit"><?= Ui::icon('logout') ?>Tizimdan chiqish</button><?= Html::endForm() ?></div><div class="workspace-note"><?= Ui::icon('check') ?><span>Shaffof baholash<small>Har bir natija dalil bilan.</small></span></div><div class="sidebar-version"><span class="dot"></span>FarDU KPI <span>Akademik</span></div></div>
</aside>
<button class="menu-overlay" data-menu-close type="button" aria-label="Menyuni yopish" tabindex="-1" hidden></button>
<div class="workspace" id="app-workspace"><div class="galaxy-horizon" aria-hidden="true"></div><header class="topbar">
<div class="topbar-location"><button class="icon-button mobile-menu" type="button" data-menu-toggle aria-controls="app-sidebar" aria-expanded="false" aria-label="Menyuni ochish"><?= Ui::icon('menu') ?></button><div class="breadcrumbs"><?= Ui::icon('building') ?><span>Ish maydoni</span><span class="crumb-divider">/</span><b><?= Ui::h($pageTitle) ?></b></div></div>
<div class="topbar-actions"><?= Html::beginForm(['kpi/applications'],'get',['class'=>'header-search','role'=>'search']) ?><label><?= Ui::icon('search') ?><span class="sr-only">Arizalarni qidirish</span><input type="search" name="q" placeholder="Arizalarni qidirish…" autocomplete="off" aria-label="Arizalarni qidirish"></label><?= Html::endForm() ?>
<button class="icon-button theme-toggle" type="button" data-theme-toggle aria-label="Tungi rejimni yoqish" aria-pressed="false"><span class="theme-moon"><?= Ui::icon('moon') ?></span><span class="theme-sun"><?= Ui::icon('sun') ?></span></button>
<?= Html::a(Ui::icon('bell'),['kpi/notifications'],['class'=>'icon-button notification-button','aria-label'=>'Bildirishnomalar']) ?>
<div class="account"><?= Html::a('<span class="avatar">'.Ui::h(mb_substr($user->name,0,1)).'</span><span class="account-info"><b>'.Ui::h($user->name).'</b><small>'.Ui::h(User::ROLES[$user->role]??$user->role).'</small></span>',['kpi/profile'],['class'=>'account-profile']) ?><?= Html::beginForm(['auth/logout'],'post') ?><button class="icon-button logout-button" title="Chiqish" aria-label="Tizimdan chiqish"><?= Ui::icon('logout') ?></button><?= Html::endForm() ?></div></div></header>
<main id="main" tabindex="-1"><?php foreach(Yii::$app->session->getAllFlashes() as $type=>$message): ?><div role="alert" class="alert <?= Ui::h($type) ?>"><?= Ui::h($message) ?></div><?php endforeach ?><?= $content ?></main>
<footer class="footer"><span>© <?= date('Y') ?> FarDU · KPI baholash tizimi</span><span>Mehnatingiz natijasi — e’tiborda.</span></footer></div>
<?php else: ?><button class="icon-button auth-theme theme-toggle" type="button" data-theme-toggle aria-label="Tungi rejimni yoqish" aria-pressed="false"><span class="theme-moon"><?= Ui::icon('moon') ?></span><span class="theme-sun"><?= Ui::icon('sun') ?></span></button><main class="auth-shell"><?= $content ?></main><?php endif ?>
<?php $this->endBody() ?></body></html><?php $this->endPage() ?>
