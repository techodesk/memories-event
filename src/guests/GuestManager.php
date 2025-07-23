<?php
/**
 * GuestManager
 *
 * Provides guest lookup, searching, and association with events.
 */
class GuestManager
{
    private \PDO $pdoEventManager;
    private \PDO $pdoMemories;

    /**
     * Constructor.
     */
    public function __construct(\PDO $pdoEventManager, \PDO $pdoMemories)
    {
        $this->pdoEventManager = $pdoEventManager;
        $this->pdoMemories = $pdoMemories;
    }

    /**
     * Fetch all guests sorted by name.
     *
     * @return array<int, array{ id:int, name:string, email:string }>
     */
    public function fetchAllGuests(): array
    {
        $stmt = $this->pdoEventManager->query("SELECT id, name, email FROM guests ORDER BY name");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Search guests by partial name or email.
     *
     * @param string $term Search term
     * @return array<int, array{ id:int, name:string, email:string }>
     */
    public function searchGuests(string $term): array
    {
        $stmt = $this->pdoEventManager->prepare(
            "SELECT id, name, email FROM guests WHERE name LIKE ? OR email LIKE ? ORDER BY name"
        );
        $like = '%' . $term . '%';
        $stmt->execute([$like, $like]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Associate guests with an event.
     *
     * @param int   $eventId
     * @param array<int, int|string> $guestIds
     * @return void
     */
    public function addGuestsToEvent(int $eventId, array $guestIds): void
    {
        $ins = $this->pdoMemories->prepare(
            "INSERT INTO event_guests (event_id, guest_id, invitation_code) VALUES (?, ?, ?)"
        );
        $codeStmt = $this->pdoEventManager->prepare("SELECT invite_code FROM guests WHERE id = ?");
        foreach ($guestIds as $gid) {
            $codeStmt->execute([$gid]);
            $code = $codeStmt->fetchColumn();
            $ins->execute([$eventId, $gid, $code]);
        }
    }
}
