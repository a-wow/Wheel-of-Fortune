<?php
require 'config.php';

$commands = '';
$itemsList = [];
$recentItemsList = [];
try {
    $pdo = getDatabaseConnection(); 
    
    $stmt = $pdo->query("SELECT id_items, name, icon FROM items");
    $itemsList = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $recent_stmt = $pdo->query("SELECT character_name, item_name, item_id, quantity, sent_at FROM sent_items ORDER BY sent_at DESC LIMIT 10");
    $recentItemsList = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (\Exception $e) {
    $commands = 'Error: ' . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['get_recent_items']) && $_POST['get_recent_items'] === '1') {
        try {
            $recent_stmt = $pdo->query("SELECT character_name, item_name, item_id, quantity, sent_at FROM sent_items ORDER BY sent_at DESC LIMIT 10");
            $recentItemsList = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response = [];
            foreach ($recentItemsList as $recentItem) {
                $itemIcon = '';
                foreach ($itemsList as $item) {
                    if ($item['id_items'] == $recentItem['item_id']) {
                        $itemIcon = $item['icon'];
                        break;
                    }
                }
                $response[] = [
                    'character_name' => $recentItem['character_name'],
                    'item_name' => $recentItem['item_name'],
                    'item_id' => $recentItem['item_id'],
                    'icon' => $itemIcon,
                    'quantity' => $recentItem['quantity']
                ];
            }
            echo json_encode($response);
            exit;
        } catch (\Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
    }
    
    if (isset($_POST['ajax_spin']) && $_POST['ajax_spin'] === '1') {
        $character = $_POST['character'] ?? '';
        if (!$character) {
            echo json_encode(['error' => 'Имя персонажа обязательно']);
            exit;
        }
        $item_id = $_POST['item_id'] ?? null;
        $item = null;
        foreach ($itemsList as $it) {
            if ($it['id_items'] == $item_id) {
                $item = $it;
                break;
            }
        }
        if (!$item) {
            echo json_encode(['error' => 'Неверный предмет']);
            exit;
        }
        $item_name = $item['name'];
        $quantity = 1;
        try {
            $client = getSoapClient();
            $command = 'send items ' . $character . ' "test" "Body" ' . $item_id . ':' . $quantity;
            $client->executeCommand(new \SoapParam($command, "command"));
            $insert_stmt = $pdo->prepare("INSERT INTO sent_items (character_name, item_name, item_id, quantity) VALUES (?, ?, ?, ?)");
            $insert_stmt->execute([$character, $item_name, $item_id, $quantity]);
            echo json_encode([
                'success' => true,
                'item_name' => $item_name,
                'item_id' => $item_id,
                'quantity' => $quantity
            ]);
            exit;
        } catch (\Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
    }
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
    <title>Колесо Фортуны</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&family=Inter:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --main-bg: linear-gradient(135deg, #232526 0%, #414345 100%);
            --card-bg: rgba(255,255,255,0.10);
            --card-blur: blur(8px);
            --accent: #00e676;
            --accent2: #2979ff;
            --danger: #ff1744;
            --text-main: #fff;
            --text-muted: #b0bec5;
            --shadow: 0 8px 32px 0 rgba(0,0,0,0.25);
            --glow: 0 0 16px 2px var(--accent2);
        }
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            font-family: 'Inter', 'Montserrat', Arial, sans-serif;
            background: var(--main-bg);
            color: var(--text-main);
            min-height: 100vh;
            overflow: hidden;
        }
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
        }
        .main-container {
            width: 100vw;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            padding-top: 120px;
            padding-bottom: 40px;
        }
        .card {
            background: var(--card-bg);
            backdrop-filter: var(--card-blur);
            border-radius: 24px;
            box-shadow: var(--shadow);
            padding: 40px 32px 32px 32px;
            margin: 0 auto 32px auto;
            max-width: 480px;
            width: 100%;
            position: relative;
            border: 1.5px solid #fff2;
            transition: box-shadow 0.3s, border 0.3s;
        }
        .card:hover {
            box-shadow: 0 12px 48px 0 #00e67633;
            border: 1.5px solid var(--accent);
        }
        h1 {
            text-align: center;
            color: var(--accent);
            margin-bottom: 24px;
            font-size: 2.2em;
            font-family: 'Montserrat', Arial, sans-serif;
            letter-spacing: 0.03em;
            font-weight: 700;
            text-shadow: 0 2px 16px #00e67644;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 16px;
            margin-bottom: 24px;
        }
        input[type="text"] {
            padding: 14px 16px;
            border: none;
            border-radius: 12px;
            font-size: 1.1em;
            background: #fff1;
            color: var(--text-main);
            box-shadow: 0 2px 8px #00e67622;
            transition: box-shadow 0.2s, background 0.2s;
        }
        input[type="text"]:focus {
            outline: none;
            background: #fff2;
            box-shadow: 0 0 0 2px var(--accent2), 0 2px 8px #2979ff33;
        }
        button {
            background: linear-gradient(90deg, var(--accent2) 0%, var(--accent) 100%);
            color: #fff;
            border: none;
            padding: 14px 0;
            border-radius: 12px;
            cursor: pointer;
            font-size: 1.1em;
            font-family: 'Montserrat', Arial, sans-serif;
            font-weight: 600;
            letter-spacing: 0.04em;
            box-shadow: 0 2px 12px #2979ff33;
            transition: background 0.2s, box-shadow 0.2s, transform 0.15s;
            position: relative;
            overflow: hidden;
        }
        button:active {
            transform: scale(0.97);
        }
        button:before {
            content: '';
            position: absolute;
            left: 50%;
            top: 50%;
            width: 0;
            height: 0;
            background: radial-gradient(circle, #fff8 0%, transparent 80%);
            transform: translate(-50%, -50%);
            opacity: 0.7;
            transition: width 0.4s, height 0.4s;
            z-index: 0;
        }
        button:hover:before {
            width: 180%;
            height: 300%;
        }
        button:hover {
            box-shadow: 0 4px 24px #00e67655, 0 2px 12px #2979ff33;
        }
        .result {
            margin-top: 24px;
            padding: 18px 16px;
            border-radius: 14px;
            background: rgba(0,230,118,0.10);
            border: 1.5px solid #00e67644;
            color: var(--text-main);
            font-size: 1.1em;
            box-shadow: 0 2px 12px #00e67622;
            min-height: 48px;
            transition: background 0.3s, border 0.3s;
        }
        .result h2 {
            margin-top: 0;
            color: var(--accent);
        }
        .error {
            color: var(--danger);
            font-weight: 600;
        }
        .top-bar {
            width: 100vw;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            background: linear-gradient(90deg, #232526 60%, #2979ff 100%);
            padding: 12px 0 10px 0;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 18px;
            box-shadow: 0 2px 16px #2979ff22;
            transition: all 0.3s ease;
        }
        .top-bar-item {
            position: relative;
            display: inline-block;
            transition: transform 0.18s;
            animation: slideInFromTop 0.5s ease-out;
        }
        @keyframes slideInFromTop {
            from {
                opacity: 0;
                transform: translateY(-20px) scale(0.8);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        .top-bar-item img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2.5px solid #fff;
            box-shadow: 0 2px 12px #2979ff33, 0 1px 4px #00e67633;
            background: #fff;
            cursor: pointer;
            transition: box-shadow 0.25s, border 0.25s, transform 0.18s;
        }
        .top-bar-item img:hover {
            box-shadow: 0 0 24px #00e67688, 0 2px 12px #2979ff33;
            border-color: var(--accent);
            transform: scale(1.08);
        }
        .top-bar-tooltip {
            display: none;
            position: absolute;
            bottom: -44px;
            left: 50%;
            transform: translateX(-50%);
            background: #232526ee;
            color: #fff;
            padding: 8px 16px;
            border-radius: 10px;
            box-shadow: 0 2px 12px #2979ff33;
            white-space: nowrap;
            font-size: 1em;
            pointer-events: none;
            z-index: 1001;
            opacity: 0;
            transition: opacity 0.25s, bottom 0.25s;
        }
        .top-bar-item img:hover + .top-bar-tooltip {
            display: block;
            opacity: 1;
            bottom: -54px;
        }
        .nav-links {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1001;
        }
        .nav-links a {
            display: inline-block;
            background: linear-gradient(90deg, var(--accent2) 0%, var(--accent) 100%);
            color: #fff;
            text-decoration: none;
            padding: 10px 18px;
            border-radius: 12px;
            font-family: 'Montserrat', Arial, sans-serif;
            font-weight: 600;
            font-size: 0.9em;
            border: 1.5px solid var(--accent);
            box-shadow: 0 2px 12px #2979ff33;
            transition: background 0.2s, box-shadow 0.2s, transform 0.15s;
        }
        .nav-links a:hover {
            box-shadow: 0 4px 24px #00e67655, 0 2px 12px #2979ff33;
            transform: translateY(-1px);
        }
        #wheel-wrapper {
            position: relative;
            width: 100%;
            max-width: 520px;
            height: 180px;
            margin: 0 auto 24px auto;
        }
        .fortune-wheel {
            width: 100%;
            height: 140px;
            border-radius: 32px;
            background: linear-gradient(135deg, #232526 60%, #2979ff 100%);
            box-shadow: 0 8px 32px #2979ff33, 0 2px 12px #00e67622;
            border: 4px solid var(--accent2);
            overflow: hidden;
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: center;
            gap: 0;
            position: relative;
            transition: box-shadow 0.3s, border 0.3s;
        }
        .fortune-sector {
            position: relative;
            width: 90px;
            height: 120px;
            border-radius: 18px;
            background: linear-gradient(135deg, #fff2 80%, #2979ff22 100%);
            box-shadow: 0 2px 12px #2979ff11;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 3px;
            transition: filter 0.3s, box-shadow 0.3s, transform 0.2s;
            z-index: 1;
        }
        .fortune-sector.active, .fortune-sector:hover {
            filter: brightness(1.18) drop-shadow(0 0 16px #00e676cc);
            box-shadow: 0 0 32px #00e67688, 0 2px 12px #2979ff33;
            z-index: 2;
            transform: scale(1.08);
        }
        .fortune-sector .item-block {
            background: none;
            border: none;
            box-shadow: none;
            padding: 0;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .fortune-sector .item-block img {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: #fff;
            border: 2.5px solid #2979ff;
            box-shadow: 0 2px 8px #2979ff22;
            padding: 4px;
            margin-bottom: 8px;
            display: block;
            margin-left: auto;
            margin-right: auto;
            transition: box-shadow 0.2s, border 0.2s;
        }
        .fortune-sector .item-block span {
            display: block;
            text-align: center;
            margin-top: 4px;
            font-size: 1em;
            color: var(--text-main);
        }
        #win-label {
            position: absolute;
            left: 50%;
            top: 148px;
            transform: translateX(-50%);
            min-width: 120px;
            text-align: center;
            font-weight: bold;
            color: var(--accent);
            font-size: 1.2em;
            background: #232526ee;
            border-radius: 12px;
            box-shadow: 0 2px 16px #00e67633;
            padding: 10px 18px;
            display: none;
            animation: fadeInUp 0.7s cubic-bezier(.4,2,.6,1);
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translate(-50%, 30px); }
            to { opacity: 1; transform: translate(-50%, 0); }
        }
        #items-menu {
            position: fixed;
            top: 80px;
            right: 36px;
            z-index: 1200;
            width: 340px;
            max-height: 70vh;
            overflow-y: auto;
            background: var(--card-bg);
            border: 2.5px solid var(--accent2);
            border-radius: 22px;
            box-shadow: 0 8px 32px #2979ff22, 0 2px 12px #00e67622;
            padding: 22px 18px 18px 18px;
            transition: box-shadow 0.2s, border 0.2s;
            display: block;
            backdrop-filter: blur(10px);
        }
        #items-menu-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
            justify-content: flex-start;
            align-items: stretch;
        }
        #items-menu-list .item-block-menu {
            background: linear-gradient(90deg, #232526 60%, #2979ff22 100%);
            border: 1.5px solid #2979ff33;
            border-radius: 12px;
            box-shadow: 0 2px 8px #2979ff11;
            padding: 12px 14px 10px 14px;
            min-width: 0;
            min-height: 60px;
            margin: 0;
            cursor: pointer;
            transition: box-shadow 0.25s, background 0.25s, border 0.25s, transform 0.18s;
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: flex-start;
            text-align: left;
            opacity: 1;
            transform: translateY(0);
            position: relative;
            overflow: hidden;
        }
        #items-menu-list .item-block-menu:hover {
            background: linear-gradient(90deg, #fffbe6 60%, #ffeaa7 100%);
            border-color: #ffc107;
            box-shadow: 0 4px 16px #ffc10733;
            transform: scale(1.025) translateX(6px);
        }
        #items-menu-list .item-block-menu img {
            width: 44px;
            height: 44px;
            margin-bottom: 0;
            margin-right: 16px;
            border-radius: 50%;
            border: 2.5px solid #2979ff;
            background: #fff;
            box-shadow: 0 2px 6px #2979ff22;
        }
        #items-menu-list .item-block-menu span {
            margin-top: 0;
            font-size: 1.08em;
            color: #fff;
            font-weight: 500;
            letter-spacing: 0.01em;
        }
        @media (max-width: 900px) {
            #items-menu { right: 10px; width: 90vw; max-width: 420px; }
        }
        @media (max-width: 600px) {
            .main-container { padding-top: 80px; }
            .card { padding: 24px 8px 18px 8px; }
            #wheel-wrapper { max-width: 98vw; }
            #items-menu { top: 70px; right: 0; left: 0; width: 100vw; border-radius: 0 0 18px 18px; }
        }
        #items-menu::-webkit-scrollbar {
            width: 12px;
            background: transparent;
        }
        #items-menu::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #2979ff 40%, #00e676 100%);
            border-radius: 10px;
            border: 2.5px solid #232526;
            box-shadow: 0 2px 8px #2979ff33;
            min-height: 40px;
            transition: background 0.3s;
        }
        #items-menu::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #00e676 20%, #2979ff 100%);
        }
        #items-menu::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.05);
            border-radius: 10px;
        }
        #items-menu {
            scrollbar-width: thin;
            scrollbar-color: #2979ff #232526;
        }
    </style>
</head>
<body>
<div class="top-bar" id="top-bar">
    <?php foreach ($recentItemsList as $recentItem): ?>
        <div class="top-bar-item">
            <img src="icons/<?php echo htmlspecialchars($itemsList[array_search($recentItem['item_id'], array_column($itemsList, 'id_items'))]['icon'] ?? ''); ?>" 
                 alt="icon" 
                 data-tooltip="<?php echo htmlspecialchars($recentItem['item_name']); ?> | <?php echo htmlspecialchars($recentItem['character_name']); ?>">
            <div class="top-bar-tooltip"></div>
        </div>
    <?php endforeach; ?>
</div>
<div id="items-menu">
    <div style="font-size:1.2em;font-weight:bold;color:var(--accent2);margin-bottom:10px;text-align:center;letter-spacing:0.02em;">Доступные предметы</div>
    <div id="items-menu-list"></div>
</div>
<div class="main-container">
    <div class="card">
        <h1>Колесо Фортуны</h1>
        <form id="fortune-form" method="POST" autocomplete="off">
            <input type="text" name="character" id="character-input" placeholder="Имя персонажа" required>
            <button type="button" id="spin-btn">Крутить Колесо Фортуны</button>
            <button type="submit" name="spin_wheel" style="display:none;">Старый способ</button>
        </form>
        <div id="wheel-wrapper">
            <div id="wheel" class="fortune-wheel">
                <div id="wheel-items" style="display:flex;flex-direction:row;align-items:center;transition:transform 0.7s;"></div>
            </div>
            <div id="win-label"></div>
        </div>
    </div>
</div>
<script>
const items = <?php echo json_encode($itemsList, JSON_UNESCAPED_UNICODE); ?>;
const wheel = document.getElementById('wheel');
const wheelItems = document.getElementById('wheel-items');
const spinBtn = document.getElementById('spin-btn');
const form = document.getElementById('fortune-form');
const characterInput = document.getElementById('character-input');
const winLabel = document.getElementById('win-label');
const topBar = document.getElementById('top-bar');

const sectorWidth = 96;
const cycles = 7;

async function updateTopBar() {
    try {
        const response = await fetch('', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                get_recent_items: '1'
            })
        });
        
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        
        const recentItems = await response.json();
        
        if (Array.isArray(recentItems)) {
            topBar.innerHTML = '';

            recentItems.forEach((item, index) => {
                const itemElement = document.createElement('div');
                itemElement.className = 'top-bar-item';
                itemElement.style.animationDelay = `${index * 0.1}s`;
                
                itemElement.innerHTML = `
                    <img src="icons/${item.icon}" 
                         alt="icon" 
                         data-tooltip="${item.item_name} | ${item.character_name}">
                    <div class="top-bar-tooltip"></div>
                `;
                
                topBar.appendChild(itemElement);
            });
            
            initializeTopBarTooltips();
        }
    } catch (error) {
        console.error('Error updating top bar:', error);
    }
}

function initializeTopBarTooltips() {
    document.querySelectorAll('.top-bar-item img').forEach(function(img) {
        const tooltip = img.parentElement.querySelector('.top-bar-tooltip');
        img.addEventListener('mouseenter', function() {
            tooltip.textContent = img.getAttribute('data-tooltip');
            tooltip.style.display = 'block';
            setTimeout(() => { tooltip.style.opacity = '1'; }, 10);
        });
        img.addEventListener('mouseleave', function() {
            tooltip.style.opacity = '0';
            setTimeout(() => { tooltip.style.display = 'none'; }, 200);
        });
    });
}

setInterval(updateTopBar, 30000);

initializeTopBarTooltips();

function getLoopedItems(items, cycles) {
    let arr = [];
    for (let i = 0; i < cycles; i++) arr = arr.concat(items);
    return arr;
}

function drawWheelSectors(loopedItems, activeIdx = null) {
    wheelItems.innerHTML = '';
    for (let i = 0; i < loopedItems.length; i++) {
        const sector = document.createElement('div');
        sector.className = 'fortune-sector' + (activeIdx === i ? ' active' : '');
        sector.style.zIndex = activeIdx === i ? 2 : 1;
        sector.innerHTML = `<div class='item-block'>
            <img src="icons/${loopedItems[i].icon}" alt="icon">
        </div>`;
        wheelItems.appendChild(sector);
        if (loopedItems.length === items.length) {
            sector.style.opacity = '0';
            sector.style.transform = 'scale(0.92) translateY(20px)';
            setTimeout(() => {
                sector.style.transition = 'opacity 0.5s cubic-bezier(.4,2,.6,1), transform 0.5s cubic-bezier(.4,2,.6,1)';
                sector.style.opacity = '1';
                sector.style.transform = 'scale(1) translateY(0)';
            }, 30 * i);
        }
    }
    if (winLabel) winLabel.style.display = 'none';
}

let spinning = false;
let loopedItems = getLoopedItems(items, cycles);
function initWheel() {
    drawWheelSectors(loopedItems);
    const containerWidth = wheel.offsetWidth;
    const centerOffset = (containerWidth - sectorWidth) / 2;
    wheelItems.style.transition = 'none';
    wheelItems.style.transform = `translateX(${-0 * sectorWidth + centerOffset}px)`;
}
initWheel();

spinBtn.onclick = async function() {
    if (spinning) return;
    if (wheelItems.childElementCount === items.length) {
        initWheel();
    }
    const character = characterInput.value.trim();
    if (!character) {
        alert('Введите имя персонажа!');
        return;
    }
    spinning = true;
    spinBtn.disabled = true;
    if (winLabel) winLabel.style.display = 'none';

    const n = items.length;
    const winIdx = Math.floor(Math.random() * n);
    const stopAt = Math.floor(cycles / 2) * n + winIdx;
    const containerWidth = wheel.offsetWidth;
    const centerOffset = (containerWidth - sectorWidth) / 2;
    const totalDistance = stopAt * sectorWidth - centerOffset;

    const duration = 5000;
    const start = performance.now();

    function animate(now) {
        const t = Math.min((now - start) / duration, 1);
        const eased = 1 - Math.pow(1 - t, 3);
        const offset = -(eased * totalDistance);
        wheelItems.style.transition = 'none';
        wheelItems.style.transform = `translateX(${offset}px)`;
        if (t < 1) {
            requestAnimationFrame(animate);
        } else {
            finish();
        }
    }
    requestAnimationFrame(animate);

    function finish() {
        drawWheelSectors(items, winIdx);
        wheelItems.style.transition = 'none';
        const centerOffsetFinal = (wheel.offsetWidth - sectorWidth) / 2;
        wheelItems.style.transform = `translateX(${-winIdx * sectorWidth + centerOffsetFinal}px)`;
        if (winLabel) {
            winLabel.innerHTML = `<img src="icons/${items[winIdx].icon}" alt="icon" style="width:36px;height:36px;vertical-align:middle;margin-right:10px;box-shadow:0 0 24px #00e67688;"> <span>${items[winIdx].name}</span>`;
            winLabel.style.display = 'block';
        }
        fetch('', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                ajax_spin: '1',
                character: character,
                item_id: items[winIdx].id_items
            })
        })
        .then(r => r.json())
        .then(data => {
            spinning = false;
            spinBtn.disabled = false;
            updateTopBar();
        })
        .catch(e => {
            spinning = false;
            spinBtn.disabled = false;
        });
    }
};
form.onsubmit = e => {
    if (!spinning) e.preventDefault();
};

const itemsMenu = document.getElementById('items-menu');
const itemsMenuList = document.getElementById('items-menu-list');

function renderItemsMenu(items) {
    itemsMenuList.innerHTML = '';
    items.forEach((item, idx) => {
        const el = document.createElement('div');
        el.className = 'item-block item-block-menu';
        el.innerHTML = `<img src="icons/${item.icon}" alt="icon"><span>${item.name}</span>`;
        itemsMenuList.appendChild(el);
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        setTimeout(() => {
            el.style.transition = 'opacity 0.5s cubic-bezier(.4,2,.6,1), transform 0.5s cubic-bezier(.4,2,.6,1)';
            el.style.opacity = '1';
            el.style.transform = 'translateY(0)';
        }, 30 * idx);
    });
}
renderItemsMenu(items);
</script>
</body>
</html>
