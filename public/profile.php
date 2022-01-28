<?php
// DBに接続
$dbh = new PDO('mysql:host=mysql;dbname=techc', 'root', '');

$user = null;
if (!empty($_GET['user_id'])) {
  $user_id = $_GET['user_id'];

  // 対象の会員情報を引く
  $select_sth = $dbh->prepare("SELECT * FROM users WHERE id = :id");
  $select_sth->execute([
      ':id' => $user_id,
  ]);
  $user = $select_sth->fetch();
}

if (empty($user)) {
  header("HTTP/1.1 404 Not Found");
  print("そのようなユーザーIDの会員情報は存在しません");
  return;
}

// 投稿データを取得。紐づく会員情報も結合し同時に取得する。
$entries_select_sth = $dbh->prepare(
  'SELECT bbs_entries.*, users.name AS user_name, users.icon_filename AS user_icon_filename'
  . ' FROM bbs_entries INNER JOIN users ON bbs_entries.user_id = users.id'
  . ' WHERE user_id = :user_id'
  . ' ORDER BY bbs_entries.created_at DESC'
);
$entries_select_sth->execute([
  ':user_id' => $user_id,
]);
$list = $entries_select_sth->fetchAll(\PDO::FETCH_ASSOC);

// 画像の追加
$sql = 'SELECT image_filename FROM bbs_images 
		WHERE id = :id';
$select_img = $dbh->prepare($sql);

foreach($list as $k => $v){
	$select_img->execute([':id' => $v['id'],]);
	$datum = $select_img->fetchAll(PDO::FETCH_ASSOC); //PDO::FETCH_ASSOC(重複表示を省く);
	if(false === $datum){
		return null;
	}
	$image = array_column($datum, 'image_filename');
	$data = [];
	foreach($image as $i){
		$data[] = '/image/' . $i;
	}
	$v['images'] = $data;
	$list[$k] = $v;
}

// フォロー状態を取得
$relationship = null;
session_start();
if (!empty($_SESSION['login_user_id'])) { // ログインしている場合
  // フォロー状態をDBから取得
  $select_sth = $dbh->prepare(
    "SELECT * FROM user_relationships"
    . " WHERE follower_user_id = :follower_user_id AND followee_user_id = :followee_user_id"
  );
  $select_sth->execute([
      ':followee_user_id' => $user['id'], // フォローされる側は閲覧しようとしているプロフィールの会員
      ':follower_user_id' => $_SESSION['login_user_id'], // フォローする側はログインしている会員
  ]);
  $relationship = $select_sth->fetch();
}

// フォローされている状態を取得
$follower_relationship = null;
if (!empty($_SESSION['login_user_id'])) { // ログインしている場合
  // フォローされている状態をDBから取得
  $select_sth = $dbh->prepare(
    "SELECT * FROM user_relationships"
    . " WHERE follower_user_id = :follower_user_id AND followee_user_id = :followee_user_id"
  );
  $select_sth->execute([
      ':follower_user_id' => $user['id'], // フォローしている側は閲覧しようとしているプロフィールの会員
      ':followee_user_id' => $_SESSION['login_user_id'], // フォローされる側はログインしている会員
  ]);
  $follower_relationship = $select_sth->fetch();
}
?>
<a href="/timeline.php">タイムラインに戻る</a>

<h1><?= htmlspecialchars($user['name']) ?> さん のプロフィール</h1>
<div class="profile" style="width: 100%; height:15em;  padding:1em;
    <?php if(!empty($user['cover_filename'])): ?>
		background-image: url('/image/<?= nl2br(htmlspecialchars($user['cover_filename'])) ?>'); 
		background-size:cover;
    <?php endif; ?>">
	<div class="line" style="overflow: hidden; margin-bottom: 10px;">
		<div class="icon">
		  <?php if(empty($user['icon_filename'])): ?>
			<div class="perfect-circle" style="width: 5em;
				height: 5em;
				border-radius: 50%;
				background: gray;
				object-fit: cover;
				border: 3px solid white; float: left; margin-right:1em;">
			</div>
		  <?php else: ?>
		  <img src="/image/<?= nl2br(htmlspecialchars($user['icon_filename'])) ?>"
			style="height: 5em; width: 5em; border-radius: 50%; object-fit: cover;border: 3px solid white; float: left; margin-right:1em;">
		  <?php endif; ?>
		</div>
		<div class="name">
			<h1><?= htmlspecialchars($user['name']) ?></h1>
		</div>
	</div>
	<div class="textarea" style="background-color: rgba(255,255,255,0.8); width:40em; height: 8em; padding:5px 15px;">
		<?php if(!empty($user['birthday'])): ?>
			<?php
			  $birthday = DateTime::createFromFormat('Y-m-d', $user['birthday']);
			  $today = new DateTime('now');
			?>
			  <p><?= $today->diff($birthday)->y . "歳(" . date('n月j日', strtotime($user['birthday'])) . "生まれ)"?></p>
		<?php endif; ?>
		<p><?= nl2br(htmlspecialchars($user['introduction'])) ?></p>
	</div>
</div>
<?php if($user['id'] === $_SESSION['login_user_id']): ?>
	<div style="margin: 1em 0;">
	  これはあなたです！<br>
	  <a href="/setting/index.php">設定画面はこちら</a>
	</div>
	<?php else: ?>
	<div style="margin: 1em 0;">
	  <?php if(empty($relationship)): // フォローしていない場合 ?>
	  <div>
		<a href="./follow.php?followee_user_id=<?= $user['id'] ?>">フォローする</a>
	  </div>
	  <?php else: // フォローしている場合 ?>
	  <div>
		<?= $relationship['created_at'] ?> にフォローしました。
	  </div>
	  <?php endif; ?>

	  <?php if(!empty($follower_relationship)): // フォローされている場合 ?>
	  <div>
		フォローされています。
	  </div>
	  <?php endif; ?>
	</div>
<?php endif; ?>
<hr>


<?php foreach($list as $entry): ?>
  <dl style="margin-bottom: 1em; padding-bottom: 1em; border-bottom: 1px solid #ccc;">
    <dt>日時</dt>
    <dd><?= $entry['created_at'] ?></dd>
    <dt>内容</dt>
    <dd>
      <?= htmlspecialchars($entry['body']) ?>
      <?php if(!empty($entry['images'])): ?>
      <div>
		<?php foreach($entry['images'] as $img):?>
			<img src="<?= $img ?>" style="max-height: 10em;">
		<? endforeach ?>
      </div>
      <?php endif; ?>
    </dd>
  </dl>
<?php endforeach ?>