<?php
// DBに接続
$dbh = new PDO('mysql:host=mysql;dbname=techc', 'root', '');
session_start();
if (empty($_SESSION['login_user_id'])) { // 非ログインの場合利用不可
  header("HTTP/1.1 302 Found");
  header("Location: /login.php");
  return;
}
$flag = $_GET['success'] ?? '';
$user = null;
// 現在のログイン情報を取得する
$user_select_sth = $dbh->prepare("SELECT * from users WHERE id = :id");
$user_select_sth->execute([':id' => $_SESSION['login_user_id']]);
$user = $user_select_sth->fetch();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
	$bind = [];
	$bind['id'] = $user['id'];
	$column = [];
	// フォームから introduction が送信されてきた場合の処理
	if (isset($_POST['name'])) {
		$bind[':name'] = $_POST['name'];
		$column[] = 'name=:name';
	}
	if (isset($_POST['birthday'])) {
		$bind[':birthday'] = $_POST['birthday'];
		$column[] = 'birthday=:birthday';
	}
	if (isset($_POST['introduction'])) {
		$bind[':introduction'] = $_POST['introduction'];
		$column[] = 'introduction=:introduction';
	}
	// WHERE句の文字列を作成
		$column_string = implode(', ', $column);
  // ログインしている会員情報のintroductionカラムを更新する
  $update_sth = $dbh->prepare('UPDATE users SET ' . $column_string . ' WHERE id = :id');
  foreach($bind as $k => $v){
	if((true === is_int($v)) || (true === is_float($v))){
		$type = \PDO::PARAM_INT;
	} else {
		$type = \PDO::PARAM_STR;
	}
	//var_dump($k, $v, $type);
	$update_sth->bindValue($k, $v, $type);
  }
  $update_sth->execute();
  // 成功したら成功したことを示すクエリパラメータつきのURLにリダイレクト
  header("HTTP/1.1 302 Found");
  header("Location: ./index.php?success=1");
  return;
}

?>
<a href="/timeline.php">タイムラインに戻る</a>
<?php if($flag === '1'):?>
	<p>変更が完了しました</p>
<?php endif;?>
<h1>現在のプロフィール</h1>
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
		  <img src="/image/<?= nl2br(htmlspecialchars($user['icon_filename'])) ?>" id="icon" 
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
			  <p><?= $today->diff($birthday)->y . "歳(" . date('Y年n月j日', strtotime($user['birthday'])) . "生まれ)"?></p>
		<?php endif; ?>
		<p><?= nl2br(htmlspecialchars($user['introduction'])) ?></p>
	</div>
</div>

<hr>
<form method="POST">
名前：<input type="text" name="name" value="<?= nl2br(htmlspecialchars($user['name'])) ?>"><br>
生年月日：<input type="date" name="birthday" value="<?= htmlspecialchars($user['birthday']) ?>"><br>
自己紹介：<br>
<textarea type="text" name="introduction" rows="5"
    ><?= htmlspecialchars($user['introduction']) ?></textarea><br>
<button type="submit">変更する</button>
</form>
<ul>
  <li><a href="./icon.php">アイコン設定</a></li>
  <li><a href="./cover.php">カバー画像設定</a></li>
</ul>