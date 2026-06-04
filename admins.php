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

    // сначала удаляем связанные языки
    $db->prepare("DELETE FROM application_languages WHERE application_id=?")->execute([$id]);

    // затем саму анкету
    $db->prepare("DELETE FROM applications WHERE id=?")->execute([$id]);

    // возврат на страницу админки
    header("Location: admin.php");
    exit();
}

// обновление данных анкеты
if (isset($_POST['update'])) {
    $id = (int)$_POST['id'];

    // обновление основных полей анкеты
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

    // возврат в список анкет
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

<!-- вывод статистики по языкам -->
<h2>Статистика по языкам</h2>
<ul>
    <?php foreach ($stats as $s): ?>
        <li><?= htmlspecialchars($s['name']) ?> — <?= $s['cnt'] ?></li>
    <?php endforeach; ?>
</ul>

<hr>

<!-- список всех анкет -->
<h2>Анкеты пользователей</h2>

<?php foreach ($applications as $app): ?>

    <?php
    // получаем языки текущего пользователя
    $langStmt->execute([$app['id']]);
    $langs = $langStmt->fetchAll(PDO::FETCH_COLUMN);
    ?>

    <div style="border:1px solid #000; margin:10px; padding:10px;">

        <?php if (isset($_GET['edit']) && $_GET['edit'] == $app['id']): ?>

            <!-- форма редактирования анкеты -->
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

                Биография:<br>
                <textarea name="bio"><?= htmlspecialchars($app['bio']) ?></textarea><br>

                <button name="update">Сохранить</button>
            </form>

        <?php else: ?>

            <!-- просмотр анкеты -->
            <b>#<?= $app['id'] ?> <?= htmlspecialchars($app['fio']) ?></b><br>
            <?= htmlspecialchars($app['email']) ?><br>
            <?= htmlspecialchars($app['phone']) ?><br>
            <?= htmlspecialchars($app['birthdate']) ?><br>
            <?= htmlspecialchars($app['gender']) ?><br>
            <i><?= htmlspecialchars($app['bio']) ?></i><br>

            <!-- список языков -->
            <b>языки:</b> <?= implode(', ', $langs) ?><br><br>

            <!-- действия администратора -->
            <a href="admin.php?edit=<?= $app['id'] ?>">Редактировать</a>
            |
            <a href="admin.php?delete=<?= $app['id'] ?>" onclick="return confirm('удалить?')">
                Удалить
            </a>

        <?php endif; ?>

    </div>

<?php endforeach; ?>