<?php

class RoomDAO {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function getAll(array $filters = []): array {
        $search    = '%' . trim($filters['search'] ?? '') . '%';
        $status    = trim($filters['status'] ?? '');
        $typeId    = (int)($filters['room_type_id'] ?? 0);

        $sql = "SELECT r.id, r.room_number, r.room_type_id, r.floor, r.status,
                       rt.name AS room_type_name, rt.description AS room_type_description,
                       rt.price_per_night, rt.capacity, rt.image
                FROM rooms r
                INNER JOIN room_types rt ON rt.id = r.room_type_id
                WHERE (r.room_number LIKE ? OR rt.name LIKE ?)";

        $params = [$search, $search];

        if ($status !== '' && in_array($status, ['available', 'booked', 'maintenance'], true)) {
            $sql .= " AND r.status = ?";
            $params[] = $status;
        }

        if ($typeId > 0) {
            $sql .= " AND r.room_type_id = ?";
            $params[] = $typeId;
        }

        $sql .= " ORDER BY r.room_number ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT r.id, r.room_number, r.room_type_id, r.floor, r.status,
                    rt.name AS room_type_name, rt.description AS room_type_description,
                    rt.price_per_night, rt.capacity, rt.image
             FROM rooms r
             INNER JOIN room_types rt ON rt.id = r.room_type_id
             WHERE r.id = ?
             LIMIT 1"
        );
        $stmt->execute([$id]);

        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function existsRoomNumber(string $roomNumber, int $ignoreId = 0): bool {
        if ($ignoreId > 0) {
            $stmt = $this->db->prepare("SELECT id FROM rooms WHERE room_number = ? AND id != ? LIMIT 1");
            $stmt->execute([$roomNumber, $ignoreId]);
        } else {
            $stmt = $this->db->prepare("SELECT id FROM rooms WHERE room_number = ? LIMIT 1");
            $stmt->execute([$roomNumber]);
        }

        return (bool)$stmt->fetch();
    }

    public function create(array $data): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO rooms (room_number, room_type_id, floor, status)
             VALUES (?, ?, ?, ?)"
        );

        return $stmt->execute([
            $data['room_number'],
            $data['room_type_id'],
            $data['floor'],
            $data['status'],
        ]);
    }

    public function update(int $id, array $data): bool {
        $stmt = $this->db->prepare(
            "UPDATE rooms
             SET room_number = ?, room_type_id = ?, floor = ?, status = ?
             WHERE id = ?"
        );

        return $stmt->execute([
            $data['room_number'],
            $data['room_type_id'],
            $data['floor'],
            $data['status'],
            $id,
        ]);
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM rooms WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getAvailableRooms(string $checkIn, string $checkOut, int $guests = 0, int $roomTypeId = 0): array {
        $sql = "SELECT r.id, r.room_number, r.room_type_id, r.floor, r.status,
                       rt.name AS room_type_name, rt.description AS room_type_description,
                       rt.price_per_night, rt.capacity, rt.image
                FROM rooms r
                INNER JOIN room_types rt ON rt.id = r.room_type_id
                WHERE r.status = 'available'
                  AND r.id NOT IN (
                    SELECT b.room_id
                    FROM bookings b
                    WHERE b.status IN ('pending', 'confirmed', 'completed')
                      AND b.check_in < ?
                      AND b.check_out > ?
                  )";

        $params = [$checkOut, $checkIn];

        if ($guests > 0) {
            $sql .= " AND rt.capacity >= ?";
            $params[] = $guests;
        }

        if ($roomTypeId > 0) {
            $sql .= " AND r.room_type_id = ?";
            $params[] = $roomTypeId;
        }

        $sql .= " ORDER BY rt.price_per_night ASC, r.room_number ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }
}
