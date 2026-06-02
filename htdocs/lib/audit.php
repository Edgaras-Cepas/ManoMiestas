<?php
/**
 * ManoMiestas — audito žurnalo įrašai (lib/audit.php).
 */
declare(strict_types=1);


// GDI: Šis kodas buvo sugeneruotas naudojant Cursor (Composer / Agent).
// Užklausa: Reikia paprasto audit log
// Rezultatas dalinai koreguotas ir pritaikyta naudojamai DB.

// Audit log funkcija, kuri paruosia ir paleidzia query.
// Iraso i lentele koks dalykas, jo id, koks veiksmas, veikejo id ir laikas
function log_audit(string $entity, int $entityId, string $action, int $actorId): void
{
    $query = db()->prepare(
        "INSERT INTO audit_log (entity, entity_id, action, actor_id, created_at)
         VALUES (:entity, :entity_id, :action, :actor_id, NOW())"
    );
    $query->execute([
        "entity" => $entity,
        "entity_id" => $entityId,
        "action" => $action,
        "actor_id" => $actorId,
    ]);
}
