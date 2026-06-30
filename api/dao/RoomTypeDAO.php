<?php

class RoomTypeDAO {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function getAll(string $search = ''): array {
        $searchTerm = '%' . trim($search) . '%';
        $sql = "SELECT id, name, description, price_per_night, capacity, image, created_at
                FROM room_types
                WHERE name LIKE ? OR COALESCE(description, '') LIKE ?
                ORDER BY created_at DESC, id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$searchTerm, $searchTerm]);

        return $stmt->fetchAll();
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT id, name, description, price_per_night, capacity, image, created_at
             FROM room_types
             WHERE id = ?
             LIMIT 1"
        );
        $stmt->execute([$id]);

        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO room_types (name, description, price_per_night, capacity, image)
             VALUES (?, ?, ?, ?, ?)"
        );

        return $stmt->execute([
            $data['name'],
            $data['description'] ?: null,
            $data['price_per_night'],
            $data['capacity'],
            $data['image'] ?: null,
        ]);
    }

    public function update(int $id, array $data): bool {
        $stmt = $this->db->prepare(
            "UPDATE room_types
             SET name = ?, description = ?, price_per_night = ?, capacity = ?, image = ?
             WHERE id = ?"
        );

        return $stmt->execute([
            $data['name'],
            $data['description'] ?: null,
            $data['price_per_night'],
            $data['capacity'],
            $data['image'] ?: null,
            $id,
        ]);
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM room_types WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
