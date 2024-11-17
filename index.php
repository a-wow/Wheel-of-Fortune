<?php
require 'config.php';

$commands = '';
$itemsList = [];
$recentItemsList = [];
try {
    $pdo = getDatabaseConnection(); 
    
    $stmt = $pdo->query("SELECT id_items, name FROM items");
    $itemsList = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $recent_stmt = $pdo->query("SELECT character_name, item_name, item_id, quantity, sent_at FROM sent_items ORDER BY sent_at DESC LIMIT 10");
    $recentItemsList = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (\Exception $e) {
    $commands = 'Error: ' . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client = getSoapClient();

    try {
        if (isset($_POST['spin_wheel'])) {
            $character = $_POST['character'];
            
            $item = $itemsList[array_rand($itemsList)];

            if ($item) {
                $item_id = $item['id_items'];
                $item_name = $item['name'];
                $quantity = 1;

                $command = 'send items ' . $character . ' "test" "Body" ' . $item_id . ':' . $quantity;
                $client->executeCommand(new \SoapParam($command, "command"));

                $insert_stmt = $pdo->prepare("INSERT INTO sent_items (character_name, item_name, item_id, quantity) VALUES (?, ?, ?, ?)");
                $insert_stmt->execute([$character, $item_name, $item_id, $quantity]);

                $commands = "Вы выиграли: " . $item_name . " (ID: " . $item_id . ")";
            } else {
                $commands = "Не удалось выбрать предмет.";
            }
        }
    } catch (\Exception $e) {
        $commands = 'Error: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Крутить Колесо Фортуны</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #e9ecef;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: auto;
            background: #ffffff;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }
        .container:hover {
            transform: scale(1.02);
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
            font-size: 2em;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 20px;
        }
        input[type="text"] {
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 8px;
            font-size: 1em;
            transition: border-color 0.2s;
        }
        input[type="text"]:focus {
            border-color: #28a745;
            outline: none;
        }
        button {
            background: #28a745;
            color: #fff;
            border: none;
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            transition: background 0.2s;
        }
        button:hover {
            background: #218838;
        }
        .result, .items-list {
            margin-top: 20px;
            padding: 15px;
            border-radius: 8px;
            background: #f8f9fa;
            border: 1px solid #ced4da;
        }
        .result h2, .items-list h2 {
            margin-top: 0;
            color: #333;
        }
        .error {
            color: red;
        }
        ul {
            list-style-type: none;
            padding: 0;
        }
        li {
            padding: 8px;
            border-bottom: 1px solid #dee2e6;
            transition: background 0.2s;
        }
        li:hover {
            background: #f1f1f1;
        }
        
        .items-container {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.item-block {
    background: #ffffff;
    border: 1px solid #ced4da;
    border-radius: 8px;
    padding: 10px;
    flex: 1 0 30%;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    transition: transform 0.2s;
    cursor: pointer;
}

.item-block:hover {
    transform: scale(1.05);
}

    </style>
</head>
<body>

<div class="container">
    <h1>Крутить Колесо Фортуны</h1>
    <form method="POST">
        <input type="text" name="character" placeholder="Имя персонажа" required>
        <button type="submit" name="spin_wheel">Крутить Колесо Фортуны</button>
    </form>

    <div class="result">
        <?php if ($commands): ?>
            <h2>Результат:</h2>
            <pre><?php echo htmlspecialchars($commands); ?></pre>
        <?php endif; ?>
    </div>

    <div class="items-list">
    <h2>Доступные предметы:</h2>
    <div class="items-container">
        <?php foreach ($itemsList as $item): ?>
            <div class="item-block">
                <div target="_blank">
                   <?php echo htmlspecialchars($item['name']); ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

    <div class="recent-items">
        <h2>Последние выигранные предметы:</h2>
        <ul>
            <?php foreach ($recentItemsList as $recentItem): ?>
                <li>
                    <?php echo htmlspecialchars($recentItem['character_name']) . " выиграл " . 
                    htmlspecialchars($recentItem['item_name']) . " (ID: " . htmlspecialchars($recentItem['item_id']) . "), количество: " . htmlspecialchars($recentItem['quantity']) . " в " . htmlspecialchars($recentItem['sent_at']); ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
</body>
</html>
