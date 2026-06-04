<?php
header('Content-Type: text/html; charset=UTF-8');

// подключение к базе данных
$user = 'u82950';
$pass = '4218692';
$dbname = 'u82950';

try {
    // создание подключения через pdo
    $db = new PDO("mysql:host=localhost;dbname=$dbname", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    // остановка при ошибке подключения
    die($e->getMessage());
}

// проверка наличия логина администратора (http basic auth)
if (!isset($_SERVER['PHP_AUTH_USER'])) {
    header('WWW-Authenticate: Basic realm="Admin Panel"');
    header('HTTP/1.0 401 Unauthorized');
    exit('Требуется авторизация');
}

// поиск администратора в базе
$stmt = $db->prepare("SELECT * FROM admin_users WHERE login = ?");
$stmt->execute([$_SERVER['PHP_AUTH_USER']]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

// проверка пароля администратора
if (!$admin || !password_verify($_SERVER['PHP_AUTH_PW'], $admin['password_hash'])) {
    header('WWW-Authenticate: Basic realm="Admin Panel"');
    header('HTTP/1.0 401 Unauthorized');
    exit('Неверный логин или пароль');
}

// удаление анкеты пользователя
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    $db->prepare("DELETE FROM application_languages WHERE application_id=?")->execute([$id]);
    $db->prepare("DELETE FROM applications WHERE id=?")->execute([$id]);

    header("Location: admin.php");
    exit();
}

// обновление данных анкеты
if (isset($_POST['update'])) {
    $id = (int)$_POST['id'];

    $stmt = $db->prepare("
        UPDATE applications
        SET fio=?, phone=?, email=?, birthdate=?, gender=?, bio=?
        WHERE id=?
    ");

    $stmt->execute([
        $_POST['fio'],
        $_POST['phone'],
        $_POST['email'],
        $_POST['birthdate'],
        $_POST['gender'],
        $_POST['bio'],
        $id
    ]);

    $db->prepare("
        DELETE FROM application_languages
        WHERE application_id=?
    ")->execute([$id]);

    if (!empty($_POST['languages'])) {

        $stmt = $db->prepare("
            INSERT INTO application_languages (application_id, language_id)
            SELECT ?, id FROM programming_languages WHERE name = ?
        ");

        foreach ($_POST['languages'] as $lang) {
            $stmt->execute([$id, $lang]);
        }
    }

    header("Location: admin.php");
    exit();
}

// получение всех анкет пользователей
$applications = $db->query("
    SELECT * FROM applications ORDER BY id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// получение языков для конкретного пользователя
$langStmt = $db->prepare("
    SELECT l.name
    FROM application_languages al
    JOIN programming_languages l ON l.id = al.language_id
    WHERE al.application_id = ?
");

// получение статистики по языкам
$stats = $db->query("
    SELECT l.name, COUNT(*) as cnt
    FROM application_languages al
    JOIN programming_languages l ON l.id = al.language_id
    GROUP BY l.name
")->fetchAll(PDO::FETCH_ASSOC);

?>

<h1>admin panel</h1>

<h2>Статистика по языкам</h2>
<ul>
    <?php foreach ($stats as $s): ?>
        <li><?= htmlspecialchars($s['name']) ?> — <?= $s['cnt'] ?></li>
    <?php endforeach; ?>
</ul>

<hr>

<h2>Анкеты пользователей</h2>

<?php foreach ($applications as $app): ?>

    <?php
    $langStmt->execute([$app['id']]);
    $langs = $langStmt->fetchAll(PDO::FETCH_COLUMN);
    ?>

    <div style="border:1px solid #000; margin:10px; padding:10px;">

        <?php if (isset($_GET['edit']) && $_GET['edit'] == $app['id']): ?>

            <form method="POST">
                <input type="hidden" name="id" value="<?= $app['id'] ?>">

                ФИО: <input name="fio" value="<?= htmlspecialchars($app['fio']) ?>"><br>
                Телефон: <input name="phone" value="<?= htmlspecialchars($app['phone']) ?>"><br>
                Email: <input name="email" value="<?= htmlspecialchars($app['email']) ?>"><br>
                Дата рождения: <input type="date" name="birthdate" value="<?= $app['birthdate'] ?>"><br>

                Пол:
                <select name="gender">
                    <option value="male" <?= $app['gender']=='male'?'selected':'' ?>>male</option>
                    <option value="female" <?= $app['gender']=='female'?'selected':'' ?>>female</option>
                </select><br>

                <b>языки:</b><br>

                <select name="languages[]" multiple>
                    <?php
                    $allLangs = ['Pascal','C','C++','JavaScript','PHP','Python','Java','Haskel','Clojure','Prolog','Scala','Go'];

                    foreach ($allLangs as $lang): ?>
                        <option value="<?= $lang ?>"
                            <?= in_array($lang, $langs) ? 'selected' : '' ?>>
                            <?= $lang ?>
                        </option>
                    <?php endforeach; ?>
                </select><br><br>

                Биография:<br>
                <textarea name="bio"><?= htmlspecialchars($app['bio']) ?></textarea><br>

                <button name="update">Сохранить</button>
            </form>

        <?php else: ?>

            <b>#<?= $app['id'] ?> <?= htmlspecialchars($app['fio']) ?></b><br>
            <?= htmlspecialchars($app['email']) ?><br>
            <?= htmlspecialchars($app['phone']) ?><br>
            <?= htmlspecialchars($app['birthdate']) ?><br>
            <?= htmlspecialchars($app['gender']) ?><br>
            <i><?= htmlspecialchars($app['bio']) ?></i><br>

            <b>языки:</b> <?= implode(', ', $langs) ?><br><br>

            <a href="admin.php?edit=<?= $app['id'] ?>">Редактировать</a>
            |
            <a href="admin.php?delete=<?= $app['id'] ?>" onclick="return confirm('удалить?')">
                Удалить
            </a>

        <?php endif; ?>

    </div>

<?php endforeach; ?>

// стиль админки
<style>
body {
    max-width: 900px;
    margin: 0 auto;
    padding: 15px;
    background-color: #e6fffa;
    color: #0f4f4a;
    font-family: Arial, sans-serif;
}

h1, h2 {
    text-align: center;
    color: #0b5d57;
}

hr {
    border: none;
    height: 1px;
    background-color: #9adbd3;
    margin: 20px 0;
}

div[style*="border:1px solid"] {
    background-color: #ffffff;
    border: 1px solid #9adbd3 !important;
    border-radius: 8px;
    padding: 15px !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

button {
    background-color: #1fb6aa;
    color: white;
    padding: 8px 12px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
}

button:hover {
    background-color: #16968d;
}

a {
    color: #0f8b82;
    text-decoration: none;
    font-weight: bold;
}

a:hover {
    text-decoration: underline;
}

input, select, textarea {
    width: 100%;
    padding: 6px;
    margin: 4px 0 10px 0;
    border: 1px solid #9adbd3;
    border-radius: 5px;
    box-sizing: border-box;
    background-color: #f6fffd;
}

textarea {
    min-height: 80px;
}

ul {
    list-style: none;
    padding: 0;
    text-align: center;
}

ul li {
    background: #d9f7f4;
    margin: 5px auto;
    padding: 6px 10px;
    border-radius: 6px;
    display: inline-block;
}
</style>
