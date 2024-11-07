<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление студентами</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php
class Database {
    private $host = "mysql.railway.internal";
    private $db_name = "railway"; // Имя базы данных
    private $username = "root"; // Имя пользователя
    private $password = "VjGDJZEAZZfMXXAkPSwyVkigRrBfhiMb"; // Пароль
    public $conn;

    public function getConnection() {
        $this->conn = null;
    
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8mb4");
        } catch (PDOException $exception) {
            echo "Ошибка подключения: " . $exception->getMessage();
        }
    
        return $this->conn;
    }
    
}

class Student {
    private $conn;
    private $table_name = "students";

    public $id;
    public $full_name;
    public $student_id;
    public $birth_date;
    public $enrollment_date;

    public function __construct($db) {
        $this->conn = $db;
    }
    public function getConnection() {
        return $this->conn;
    }

    public function createTable() {
        $query = "CREATE TABLE IF NOT EXISTS " . $this->table_name . " (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(255) NOT NULL,
            student_id VARCHAR(50) NOT NULL,
            birth_date DATE NOT NULL,
            enrollment_date DATE NOT NULL
        )";

        $stmt = $this->conn->prepare($query);
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function read($enrollment_after = null, $enrollment_before = null, $age_from = null, $age_to = null) {
        $query = "SELECT * FROM " . $this->table_name;
        
        
        $conditions = [];
        if ($enrollment_after) {
            $conditions[] = "enrollment_date > :enrollment_after";
        }
        if ($enrollment_before) {
            $conditions[] = "enrollment_date < :enrollment_before";
        }
        if ($age_from !== null || $age_to !== null) {
            $today = date('Y-m-d');
            if ($age_from) {
                $date_from = date('Y-m-d', strtotime($today . " - $age_from years"));
                $conditions[] = "birth_date <= :date_from";
            }
            if ($age_to) {
                $date_to = date('Y-m-d', strtotime($today . " - $age_to years"));
                $conditions[] = "birth_date >= :date_to";
            }
        }

        if (count($conditions) > 0) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        $stmt = $this->conn->prepare($query);
        
        if ($enrollment_after) {
            $stmt->bindParam(':enrollment_after', $enrollment_after);
        }
        if ($enrollment_before) {
            $stmt->bindParam(':enrollment_before', $enrollment_before);
        }
        if (isset($date_from)) {
            $stmt->bindParam(':date_from', $date_from);
        }
        if (isset($date_to)) {
            $stmt->bindParam(':date_to', $date_to);
        }

        $stmt->execute();
        return $stmt;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET
            full_name=:full_name, student_id=:student_id, birth_date=:birth_date, enrollment_date=:enrollment_date";
    
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            echo "Ошибка подготовки запроса: " . implode(", ", $this->conn->errorInfo());
            return false; // Если подготовка запроса не удалась, возвращаем false
        }
    
        $stmt->bindParam(":full_name", $this->full_name);
        $stmt->bindParam(":student_id", $this->student_id);
        $stmt->bindParam(":birth_date", $this->birth_date);
        $stmt->bindParam(":enrollment_date", $this->enrollment_date);
    
        // Выполняем запрос
        if ($stmt->execute()) {
            return true; // Если выполнение успешное, возвращаем true
        } else {
            // Если выполнение не удалось, выводим информацию об ошибке
            echo "Ошибка выполнения запроса: " . implode(", ", $stmt->errorInfo());
            return false; // Возвращаем false
        }
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }
}

$database = new Database();
$db = $database->getConnection();

$student = new Student($db);

//if ($db) {
  //  echo "Подключение к базе данных успешно!";
//} else {
  //  echo "Ошибка подключения к базе данных.";
//}

$student->createTable();

if (isset($_POST['add'])) {
    $student->full_name = $_POST['full_name'];
    $student->student_id = $_POST['student_id'];
    $student->birth_date = $_POST['birth_date'];
    $student->enrollment_date = $_POST['enrollment_date'];

    if ($student->create()) {
        echo "Студент добавлен!";
    } else {
        echo "Ошибка добавления!";
        // Выводим информацию об ошибке, если добавление не удалось
        print_r($student->getConnection()->errorInfo());
    }
}

if (isset($_POST['delete'])) {
    $student->id = $_POST['id'];

    if ($student->delete()) {
        echo "Студент удален!";
    } else {
        echo "Ошибка удаления!";
    }
}

$enrollment_after = isset($_POST['enrollment_after']) ? $_POST['enrollment_after'] : null;
$enrollment_before = isset($_POST['enrollment_before']) ? $_POST['enrollment_before'] : null;
$age_from = isset($_POST['age_from']) ? $_POST['age_from'] : null;
$age_to = isset($_POST['age_to']) ? $_POST['age_to'] : null;


if (isset($_POST['reset_filters'])) {
    $enrollment_after = null;
    $enrollment_before = null;
    $age_from = null;
    $age_to = null;
}

$stmt = $student->read($enrollment_after, $enrollment_before, $age_from, $age_to);

echo "<h2>Список студентов</h2>";
echo "<table>";
echo "<tr><th>ID</th><th>ФИО</th><th>Номер зачетной книжки</th><th>Дата рождения</th><th>Дата поступления</th></tr>";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    extract($row);
    echo "<tr>
            <td>{$id}</td>
            <td>{$full_name}</td>
            <td>{$student_id}</td>
            <td>{$birth_date}</td>
            <td>{$enrollment_date}</td>
          </tr>";
}
echo "</table>";

?>

<h1>Добавить школьника</h1>
<form method="post" action="index.php">
    <label>ФИО:</label><br>
    <input type="text" name="full_name" required><br>
    <label>Номер зачетной книжки:</label><br>
    <input type="text" name="student_id" required><br>
    <label>Дата рождения:</label><br>
    <input type="date" name="birth_date" required><br>
    <label>Дата поступления:</label><br>
    <input type="date" name="enrollment_date" required><br>
    <input type="submit" name="add" value="Добавить"><br>
</form>

<h1>Удалить студента</h1>
<form method="post" action="index.php">
    <label>ID студента:</label><br>
    <input type="text" name="id" required><br>
    <input type="submit" name="delete" value="Удалить"><br>
</form>

<h1>Фильтрация студентов</h1>
<form method="post" action="index.php">
    <h2>Фильтрация по дате поступления</h2>
    <label>Поступившие после:</label><br>
    <input type="date" name="enrollment_after" value="<?= isset($enrollment_after) ? $enrollment_after : '' ?>"><br>
    <label>Поступившие до:</label><br>
    <input type="date" name="enrollment_before" value="<?= isset($enrollment_before) ? $enrollment_before : '' ?>"><br>

    <h2>Фильтрация по возрасту</h2>
    <label>Возраст от:</label><br>
    <input type="number" name="age_from" min="0" value="<?= isset($age_from) ? $age_from : '' ?>"><br>
    <label>Возраст до:</label><br>
    <input type="number" name="age_to" min="0" value="<?= isset($age_to) ? $age_to : '' ?>"><br>


    <input type="submit" name="filter_all" value="Фильтровать"><br>
</form>


<form method="post" action="index.php">
    <input type="submit" name="reset_filters" value="Сбросить фильтры" class="reset-button">
</form>





</body>
</html>
