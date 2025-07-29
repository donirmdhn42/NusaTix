<?php
function getAllFilms($conn) {
    $sql = "SELECT id_film, title, director, genre, duration, description, poster, release_date, status FROM films ORDER BY created_at DESC";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getFilmById($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM films WHERE id_film = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $film = $result->fetch_assoc();
    $stmt->close();
    return $film;
}

function saveFilm($conn, $data) {
    $id = intval($data['id_film'] ?? 0);
    $poster_path = $data['poster'] ?? null;

    if ($id > 0) { 
        $stmt = $conn->prepare("UPDATE films SET title=?, director=?, genre=?, duration=?, description=?, poster=?, release_date=?, status=? WHERE id_film=?");
        $stmt->bind_param("sssissssi", $data['title'], $data['director'], $data['genre'], $data['duration'], $data['description'], $poster_path, $data['release_date'], $data['status'], $id);
    } else { 
        $stmt = $conn->prepare("INSERT INTO films (title, director, genre, duration, description, poster, release_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssissss", $data['title'], $data['director'], $data['genre'], $data['duration'], $data['description'], $poster_path, $data['release_date'], $data['status']);
    }

    $is_success = $stmt->execute();
    $stmt->close();
    return $is_success;
}

function deleteFilmById($conn, $id) {
    $stmt = $conn->prepare("DELETE FROM films WHERE id_film = ?");
    $stmt->bind_param("i", $id);
    $is_success = $stmt->execute();
    $stmt->close();
    return $is_success;
}

function getRecommendedFilms($conn, $limit = 3) {
    $sql = "SELECT id_film, title, genre, poster FROM films WHERE status = 'now_showing' ORDER BY RAND() LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $films = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $films;
}

function archiveFinishedFilms($conn) {
    $sql_get_showing = "SELECT id_film FROM films WHERE status = 'now_showing'";
    $result = $conn->query($sql_get_showing);
    $showing_films = $result->fetch_all(MYSQLI_ASSOC);

    if (empty($showing_films)) {
        return 0; 
    }

    $films_to_archive = [];
    $today = date('Y-m-d');

    foreach ($showing_films as $film) {
        $id_film = $film['id_film'];
        
        $stmt = $conn->prepare("SELECT COUNT(id_schedule) as future_schedules FROM schedules WHERE id_film = ? AND show_date >= ?");
        $stmt->bind_param("is", $id_film, $today);
        $stmt->execute();
        $count_result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($count_result['future_schedules'] == 0) {
            $films_to_archive[] = $id_film;
        }
    }

    if (empty($films_to_archive)) {
        return 0; 
    }

    $ids_to_archive_str = implode(',', $films_to_archive);
    $sql_update = "UPDATE films SET status = 'archived' WHERE id_film IN ($ids_to_archive_str)";
    
    $conn->query($sql_update);
    
    return $conn->affected_rows;
}
?>