<?php
use yii\helpers\Html;
use app\services\Ui;
use app\models\User;
$user=Yii::$app->user->identity;$route=$this->context->route;
$navigation=[['kpi/index','Boshqaruv paneli','◫'],['kpi/applications','Arizalar','▤'],['kpi/create','Yangi faoliyat','＋'],['kpi/criteria','KPI mezonlari','▦'],['kpi/reports','Reyting va hisobot','↗'],['kpi/notifications','Bildirishnomalar','◉'],['kpi/profile','Shaxsiy profil','◎']];
?>
<?php $this->beginPage() ?>
<!doctype html><html lang="uz"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= Ui::h($this->title?:'KPI · Universitet') ?></title><?= Html::csrfMetaTags() ?><link rel="stylesheet" href="<?= Ui::h(Yii::$app->request->baseUrl) ?>/css/app.css"><?php $this->head() ?></head>
<body><?php $this->beginBody() ?>
<?php if($user): ?>
<aside class="sidebar"><a class="brand" href="<?= Html::encode(yii\helpers\Url::to(['kpi/index'])) ?>"><span class="brand-mark">K</span><span>KPI<span class="brand-sub">UNIVERSITET PLATFORMASI</span></span></a>
<div class="nav-label">ISH MAYDONI</div><nav aria-label="Asosiy menyu"><?php foreach($navigation as [$url,$label,$icon]): ?><?= Html::a('<span class="nav-icon">'.$icon.'</span>'.Ui::h($label),[$url],['class'=>$route===$url?'nav-link active':'nav-link']) ?><?php endforeach ?>
<?php if($user->canReview()): ?><?= Html::a('<span class="nav-icon">✓</span>Tekshiruv navbati',['kpi/applications','queue'=>1],['class'=>'nav-link']) ?><?php endif ?>
<?php if($user->isAdmin()): ?><div class="nav-label">BOSHQARUV</div><?php foreach([['users','Foydalanuvchilar'],['structure','Universitet tuzilmasi'],['periods','Baholash davrlari'],['rules','Hisoblash qoidalari'],['route','Tasdiqlash bosqichlari'],['hemis','HEMIS integratsiyasi'],['audit','Amallar jurnali']] as [$url,$label]): ?><?= Html::a(Ui::h($label),['admin/'.$url],['class'=>$route==='admin/'.$url?'nav-link active':'nav-link']) ?><?php endforeach ?><?php endif ?></nav>
<div class="sidebar-bottom"><span class="dot"></span> KPI · Hisoblash va tahlil <small>Mezonlar asosida shaffof baholash</small></div></aside>
<div class="workspace"><header class="topbar"><div><span class="eyebrow">UNIVERSITET / KPI</span><p>Natijalar va rivojlanish</p></div><div class="account"><span class="avatar"><?= Ui::h(mb_substr($user->name,0,1)) ?></span><div><b><?= Ui::h($user->name) ?></b><small><?= Ui::h(User::ROLES[$user->role]??$user->role) ?></small></div><?= Html::beginForm(['auth/logout'],'post') ?><button class="icon-button" title="Chiqish" aria-label="Tizimdan chiqish">↪</button><?= Html::endForm() ?></div></header>
<main id="main"><?php foreach(Yii::$app->session->getAllFlashes() as $type=>$message): ?><div role="alert" class="alert <?= Ui::h($type) ?>"><?= Ui::h($message) ?></div><?php endforeach ?><?= $content ?></main><footer class="footer">KPI · Mehnatingiz natijasi raqamlarda.<span><?= date('Y') ?></span></footer></div>
<?php else: ?><main class="auth-shell"><?= $content ?></main><?php endif ?>
<?php $this->endBody() ?></body></html><?php $this->endPage() ?>
