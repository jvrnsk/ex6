<?php
header('Content-Type: text/html; charset=UTF-8');
//начало сессии
session_start();

// Функции для работы с cookies
function getFormData($field) {
    return $_COOKIE["form_$field"] ?? '';
}

function setFormCookie($name, $value, $expire = 0) {
    setcookie("form_$name", $value, $expire, '/');
}

function setErrorCookie($name, $message) {
    setcookie("error_$name", $message, 0, '/');
}
// Авторизация
if (isset($_POST['login_submit'])) {

    $user = 'u82950';
    $pass = '4218692';
    $dbname = 'u82950';

    try {
        $db = new PDO("mysql:host=localhost;dbname=$dbname", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        $stmt = $db->prepare("SELECT * FROM applications WHERE login = ?");
        $stmt->execute([$_POST['login']]);

        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($userData && password_verify($_POST['password'], $userData['password_hash'])) {

            $_SESSION['user_id'] = $userData['id'];

            // флаг успешного входа
            $_SESSION['login_success'] = 1;

            header('Location: index.php');
            exit();

        } else {

            setcookie('login_error', 'Неверный логин или пароль', 0, '/');
            header('Location: index.php');
            exit();
        }

    } catch (PDOException $e) {
        die($e->getMessage());
    }
}
// Обработка POST-запроса (отправка формы)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['login_submit'])) {
    $errors = [];
    $allowedLanguages = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskel', 'Clojure', 'Prolog', 'Scala', 'Go'];

    // Валидация ФИО
    if (empty($_POST['fio'])) {
        $errors['fio'] = 'Заполните ФИО.';
        setErrorCookie('fio', $errors['fio']);
    } elseif (!preg_match('/^[а-яА-ЯёЁa-zA-Z\s]+$/u', $_POST['fio'])) {
        $errors['fio'] = 'Допустимы только буквы и пробелы';
        setErrorCookie('fio', $errors['fio']);
    } elseif (strlen($_POST['fio']) > 150) {
        $errors['fio'] = 'Не более 150 символов';
        setErrorCookie('fio', $errors['fio']);
    }
    setFormCookie('fio', $_POST['fio']); // <-- Сохраняем всегда

    // Валидация телефона
    if (empty($_POST['phone'])) {
        $errors['phone'] = 'Заполните телефон.';
        setErrorCookie('phone', $errors['phone']);
    } elseif (!preg_match('/^\+?\d{10,15}$/', $_POST['phone'])) {
        $errors['phone'] = 'От 10 до 15 цифр, можно начинать с +';
        setErrorCookie('phone', $errors['phone']);
    }
    setFormCookie('phone', $_POST['phone']); // <-- Сохраняем всегда

    //функции для генерации логина и пароля
    function generateLogin() {
    return 'user' . rand(1000, 9999);
}

function generatePassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    return substr(str_shuffle($chars), 0, $length);
}

    // Валидация email
    if (empty($_POST['email'])) {
        $errors['email'] = 'Заполните email.';
        setErrorCookie('email', $errors['email']);
    } elseif (!preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $_POST['email'])) {
        $errors['email'] = 'Некорректный email';
        setErrorCookie('email', $errors['email']);
    }
    setFormCookie('email', $_POST['email']); // <-- Сохраняем всегда

    // Валидация даты рождения
    if (empty($_POST['birthdate'])) {
        $errors['birthdate'] = 'Укажите дату рождения';
        setErrorCookie('birthdate', $errors['birthdate']);
    } else {
        $birthdate = DateTime::createFromFormat('Y-m-d', $_POST['birthdate']);
        $today = new DateTime();
        $minAge = new DateTime('-150 years');
        if (!$birthdate || $birthdate > $today || $birthdate < $minAge) {
            $errors['birthdate'] = 'Некорректная дата';
            setErrorCookie('birthdate', $errors['birthdate']);
        }
    }
    setFormCookie('birthdate', $_POST['birthdate']); // <-- Сохраняем всегда

    // Валидация пола
    if (empty($_POST['gender'])) {
        $errors['gender'] = 'Укажите пол';
        setErrorCookie('gender', $errors['gender']);
    } elseif (!in_array($_POST['gender'], ['male', 'female'])) {
        $errors['gender'] = 'Выберите из списка';
        setErrorCookie('gender', $errors['gender']);
    }
    setFormCookie('gender', $_POST['gender']); // <-- Сохраняем всегда

    // Валидация языков программирования
    if (empty($_POST['languages'])) {
        $errors['languages'] = 'Выберите хотя бы один язык';
        setErrorCookie('languages', $errors['languages']);
    } else {
        foreach ($_POST['languages'] as $lang) {
            if (!in_array($lang, $allowedLanguages)) {
                $errors['languages'] = 'Недопустимый язык';
                setErrorCookie('languages', $errors['languages']);
                break;
            }
        }
        setFormCookie('languages', implode(',', $_POST['languages'])); // <-- Сохраняем всегда
    }

    // Валидация биографии
    if (empty($_POST['bio'])) {
        $errors['bio'] = 'Заполните биографию';
        setErrorCookie('bio', $errors['bio']);
    } elseif (strlen($_POST['bio']) > 5000) {
        $errors['bio'] = 'Не более 5000 символов';
        setErrorCookie('bio', $errors['bio']);
    }
    setFormCookie('bio', $_POST['bio']); // <-- Сохраняем всегда

    // Валидация чекбокса
    if (empty($_POST['contract'])) {
        $errors['contract'] = 'Необходимо согласие';
        setErrorCookie('contract', $errors['contract']);
    } else {
        setFormCookie('contract', '1'); // <-- Сохраняем всегда
    }

    // Если есть ошибки — перенаправляем обратно
    if (!empty($errors)) {
        header('Location: index.php');
        exit();
    }

    // Подключение к БД
    $user = 'u82950';
    $pass = '4218692';
    $dbname = 'u82950';
    try {
        $db = new PDO("mysql:host=localhost;dbname=$dbname", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        $db->beginTransaction();

        // Проверяем: редактирование или новая анкета
        $isEdit = isset($_SESSION['user_id']);
        if (!$isEdit) {
            // Генерация логина и пароля
            $generatedLogin = generateLogin();
            $generatedPassword = generatePassword();
            // Хешируем пароль
            $passwordHash = password_hash($generatedPassword, PASSWORD_DEFAULT);    
    }

    // Сохранение основной информации
    if ($isEdit) {

        // UPDATE существующей анкеты
        $stmt = $db->prepare("
            UPDATE applications
            SET fio=?, phone=?, email=?, birthdate=?, gender=?, bio=?, contract_agreed=?
            WHERE id=?
        ");

        $stmt->execute([
            $_POST['fio'],
            $_POST['phone'],
            $_POST['email'],
            $_POST['birthdate'],
            $_POST['gender'],
            $_POST['bio'],
            isset($_POST['contract']) ? 1 : 0,
            $_SESSION['user_id']
        ]);

        $applicationId = $_SESSION['user_id'];

        // Удаляем старые языки
        $stmt = $db->prepare("
            DELETE FROM application_languages
            WHERE application_id=?
        ");
        $stmt->execute([$applicationId]);

    } else {

        // INSERT новой анкеты
        $stmt = $db->prepare("
            INSERT INTO applications
            (fio, phone, email, birthdate, gender, bio, contract_agreed, login, password_hash)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $_POST['fio'],
            $_POST['phone'],
            $_POST['email'],
            $_POST['birthdate'],
            $_POST['gender'],
            $_POST['bio'],
            isset($_POST['contract']) ? 1 : 0,
            $generatedLogin,
            $passwordHash
        ]);

        $applicationId = $db->lastInsertId();
    }

// Сохранение языков
$stmt = $db->prepare("
    INSERT INTO application_languages (application_id, language_id)
    SELECT ?, id FROM programming_languages WHERE name = ?
");

foreach ($_POST['languages'] as $lang) {
    $stmt->execute([$applicationId, $lang]);
}

$db->commit();

if (!$isEdit) {
    $_SESSION['generated_login'] = $generatedLogin;
    $_SESSION['generated_password'] = $generatedPassword;
}

// очистка cookies ошибок
foreach ($_COOKIE as $name => $value) {
    if (strpos($name, 'error_') === 0) {
        setcookie($name, '', time() - 3600, '/');
    }
}

header('Location: index.php?success=1&id='.$applicationId);
exit();
    } catch (PDOException $e) {
        if (isset($db)) {
            $db->rollBack();
        }
        setErrorCookie('db', 'Ошибка сохранения: '.$e->getMessage());
        header('Location: index.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Анкета</title>
</head>
    <style>
body {
    max-width: 700px;
    margin: 0 auto;
    padding: 10px;
    background-color: #f398c3;
    color: #c93b6b;
}

h1 {
    text-align: center;
}

.success-message{
    color: white;
    font: 16pt bold;
    margin-top: 10px;
    padding: 10px;
    border-radius: 5px;
    background-color: #606d42;
    border: none;
    border-radius: 4px;
    text-align: center;
}

form{
    background-color: #f8f8e0;
    padding: 10px;
    border-radius: 4px;
height: 100%;
}

.form-group {
    margin-bottom: 15px;
}

label {
    display: block;
    font-weight: bold;
}

input[type="text"],
input[type="tel"],
input[type="email"],
input[type="date"],
textarea,
select {
margin-top: 5px;
    width: 100%;
    padding: 8px;
    border: 1px solid #9eb370;
    border-radius: 4px;
    box-sizing: border-box;
}

textarea {
    height: 100px;
}

.radio-group, .checkbox-group {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 5px 0;
}

.error {
    color: red;
    font-size: 0.9em;
    margin-top: 5px;
}

button {
    background-color: #9eb370;
    color: white;
    padding: 10px 15px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 16px;
}

button:hover {
    background-color: #606d42;
}
</style>
<body>

<!-- ФОРМА ВХОДА -->
<form method="POST" style="margin-bottom:20px;">
    <h2>Вход</h2>

    <input type="text" name="login" placeholder="Логин">
    <input type="password" name="password" placeholder="Пароль">

    <button type="submit" name="login_submit">
        Войти
    </button>

    <?php if (isset($_COOKIE['login_error'])): ?>
        <div class="error">
            <?= htmlspecialchars($_COOKIE['login_error']) ?>
        </div>
    <?php endif; ?>
</form>

<!-- кнопка перехода в админку -->
<div style="margin-bottom: 20px;">
    <form action="admin.php" method="GET">
        <button type="submit">
            вход в админку
        </button>
    </form>
</div>

<?php if (isset($_SESSION['login_success'])): ?>
    <div class="success-message">
        Вход выполнен успешно!
    </div>
    <?php unset($_SESSION['login_success']); ?>
<?php endif; ?>


<!-- ФОРМА АНКЕТЫ -->
<form action="index.php" method="POST">

    <h1>Анкета</h1>

    <!-- ФИО -->
    <div class="form-group">
        <label for="fio">ФИО:</label>
        <input type="text" id="fio" name="fio"
               value="<?= htmlspecialchars(getFormData('fio')) ?>"
               class="<?= isset($_COOKIE['error_fio']) ? 'error-field' : '' ?>">
        <?php if (isset($_COOKIE['error_fio'])): ?>
            <div class="error"><?= htmlspecialchars($_COOKIE['error_fio']) ?></div>
        <?php endif; ?>
    </div>

    <!-- Телефон -->
    <div class="form-group">
        <label for="phone">Телефон:</label>
        <input type="tel" id="phone" name="phone"
               value="<?= htmlspecialchars(getFormData('phone')) ?>"
               class="<?= isset($_COOKIE['error_phone']) ? 'error-field' : '' ?>">
        <?php if (isset($_COOKIE['error_phone'])): ?>
            <div class="error"><?= htmlspecialchars($_COOKIE['error_phone']) ?></div>
        <?php endif; ?>
    </div>

    <!-- Email -->
    <div class="form-group">
        <label for="email">Email:</label>
        <input type="email" id="email" name="email"
               value="<?= htmlspecialchars(getFormData('email')) ?>"
               class="<?= isset($_COOKIE['error_email']) ? 'error-field' : '' ?>">
        <?php if (isset($_COOKIE['error_email'])): ?>
            <div class="error"><?= htmlspecialchars($_COOKIE['error_email']) ?></div>
        <?php endif; ?>
    </div>

    <!-- Дата рождения -->
    <div class="form-group">
        <label for="birthdate">Дата рождения:</label>
        <input type="date" id="birthdate" name="birthdate"
               value="<?= htmlspecialchars(getFormData('birthdate')) ?>"
               class="<?= isset($_COOKIE['error_birthdate']) ? 'error-field' : '' ?>">
        <?php if (isset($_COOKIE['error_birthdate'])): ?>
            <div class="error"><?= htmlspecialchars($_COOKIE['error_birthdate']) ?></div>
        <?php endif; ?>
    </div>

    <!-- Пол -->
    <div class="form-group">
        <label>Пол:</label>

        <div class="radio-group">
            <input type="radio" id="male" name="gender" value="male"
                <?= getFormData('gender') == 'male' ? 'checked' : '' ?>>
            <label for="male">Мужской</label>
        </div>

        <div class="radio-group">
            <input type="radio" id="female" name="gender" value="female"
                <?= getFormData('gender') == 'female' ? 'checked' : '' ?>>
            <label for="female">Женский</label>
        </div>

        <?php if (isset($_COOKIE['error_gender'])): ?>
            <div class="error"><?= htmlspecialchars($_COOKIE['error_gender']) ?></div>
        <?php endif; ?>
    </div>

    <!-- Языки -->
    <div class="form-group">
        <label for="languages">Языки программирования:</label>

        <select name="languages[]" multiple>
            <?php
            $selectedLangs = explode(',', getFormData('languages'));
            $options = ['Pascal','C','C++','JavaScript','PHP','Python','Java','Haskel','Clojure','Prolog','Scala','Go'];

            foreach ($options as $lang): ?>
                <option value="<?= $lang ?>"
                    <?= in_array($lang, $selectedLangs) ? 'selected' : '' ?>>
                    <?= $lang ?>
                </option>
            <?php endforeach; ?>
        </select>

        <?php if (isset($_COOKIE['error_languages'])): ?>
            <div class="error"><?= htmlspecialchars($_COOKIE['error_languages']) ?></div>
        <?php endif; ?>
    </div>

    <!-- Биография -->
    <div class="form-group">
        <label for="bio">Биография:</label>
        <textarea name="bio"><?= htmlspecialchars(getFormData('bio')) ?></textarea>

        <?php if (isset($_COOKIE['error_bio'])): ?>
            <div class="error"><?= htmlspecialchars($_COOKIE['error_bio']) ?></div>
        <?php endif; ?>
    </div>

    <!-- Согласие -->
    <div class="form-group">
        <label>
            <input type="checkbox" name="contract" value="1"
                <?= getFormData('contract') ? 'checked' : '' ?>>
            С контрактом ознакомлен(-а)
        </label>

        <?php if (isset($_COOKIE['error_contract'])): ?>
            <div class="error"><?= htmlspecialchars($_COOKIE['error_contract']) ?></div>
        <?php endif; ?>
    </div>

    <button type="submit">Сохранить</button>

        <?php if (isset($_GET['success'])): ?>
            <div class="success-message">
    <?php if (isset($_SESSION['generated_login'])): ?>
        Спасибо за заполнение анкеты!<br><br>
        Ваш логин:
        <b><?= $_SESSION['generated_login'] ?></b><br>

        Ваш пароль:
        <b><?= $_SESSION['generated_password'] ?></b><br><br>

        Сохраните их! Они показываются только один раз.

        <?php
        unset($_SESSION['generated_login']);
        unset($_SESSION['generated_password']);
        ?>

    <?php else: ?>
        Данные успешно обновлены!
    <?php endif; ?>
</div>
        <?php endif; ?>
        <?php if (isset($_COOKIE['error_db'])): ?>
            <div class="error"><?= htmlspecialchars($_COOKIE['error_db']) ?></div>
        <?php endif; ?>
    </form>
</body>
</html>