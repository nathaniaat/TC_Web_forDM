<?php
header('Content-Type: application/json');

$conn = new mysqli('localhost', 'root', '', 'event_tracker');

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

$action = $_POST['action'] ?? '';

// Update attendance (hadir/ijin)
if ($action === 'update_attendance') {
    $type = $_POST['type'] ?? '';
    $update = $_POST['update'] ?? '';
    
    if ($type === 'hadir') {
        if ($update === 'plus') {
            $conn->query("UPDATE attendance SET hadir = hadir + 1 WHERE event_id = 1");
        } else {
            $conn->query("UPDATE attendance SET hadir = GREATEST(0, hadir - 1) WHERE event_id = 1");
        }
    } else if ($type === 'ijin') {
        if ($update === 'plus') {
            $conn->query("UPDATE attendance SET ijin_keluar = ijin_keluar + 1 WHERE event_id = 1");
        } else {
            $conn->query("UPDATE attendance SET ijin_keluar = GREATEST(0, ijin_keluar - 1) WHERE event_id = 1");
        }
    }
    
    $result = $conn->query("SELECT hadir, ijin_keluar FROM attendance WHERE event_id = 1");
    $data = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'hadir' => $data['hadir'],
        'ijin' => $data['ijin_keluar']
    ]);
    exit;
}

// Update altar call counter
if ($action === 'update_altar') {
    $id = intval($_POST['altar_id'] ?? 0);
    $update = $_POST['update'] ?? '';
    
    if ($update === 'plus') {
        $conn->query("UPDATE altar_calls SET counter = counter + 1 WHERE id = $id");
    } else {
        $conn->query("UPDATE altar_calls SET counter = GREATEST(0, counter - 1) WHERE id = $id");
    }
    
    $result = $conn->query("SELECT counter FROM altar_calls WHERE id = $id");
    $data = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'counter' => $data['counter']
    ]);
    exit;
}

// Add new altar call
if ($action === 'add_altar') {
    $nama = $conn->real_escape_string($_POST['nama'] ?? '');
    
    // Get max urutan
    $result = $conn->query("SELECT MAX(urutan) as max_urutan FROM altar_calls WHERE event_id = 1");
    $row = $result->fetch_assoc();
    $urutan = ($row['max_urutan'] ?? 0) + 1;
    
    $conn->query("INSERT INTO altar_calls (event_id, nama_altar_call, urutan, counter) VALUES (1, '$nama', $urutan, 0)");
    $id = $conn->insert_id;
    
    echo json_encode([
        'success' => true,
        'id' => $id,
        'nama' => $nama
    ]);
    exit;
}

// Sync data (for real-time updates)
if ($action === 'sync_data') {
    // Get attendance
    $att = $conn->query("SELECT hadir, ijin_keluar FROM attendance WHERE event_id = 1")->fetch_assoc();
    
    // Get all altar calls
    $altars = $conn->query("SELECT id, counter FROM altar_calls WHERE event_id = 1");
    $altar_data = [];
    while($row = $altars->fetch_assoc()) {
        $altar_data[$row['id']] = $row['counter'];
    }
    
    echo json_encode([
        'success' => true,
        'hadir' => $att['hadir'],
        'ijin' => $att['ijin_keluar'],
        'altar_calls' => $altar_data
    ]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
$conn->close();
?>