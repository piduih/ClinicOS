<?php
declare(strict_types=1);

namespace ClinicOS\Audit;

use PDO;
use Throwable;

final class AuditLogger
{
    /**
     * Audit metadata is intentionally limited to non-sensitive identifiers.
     * Never pass passwords, tokens, patient details, notes or form payloads.
     */
    public static function record(
        PDO $pdo,
        ?int $actorId,
        string $event,
        ?string $entityType = null,
        ?int $entityId = null
    ): bool {
        $allowed = [
            'auth.login_succeeded', 'auth.login_failed', 'auth.logout',
            'patient.created', 'patient.updated', 'patient.viewed',
            'appointment.created', 'appointment.updated', 'appointment.status_changed',
            'user.created', 'user.updated', 'user.activated', 'user.deactivated',
        ];
        if (!in_array($event, $allowed, true)) {
            return false;
        }

        try {
            $statement = $pdo->prepare(
                'INSERT INTO audit_logs (actor_user_id, event, entity_type, entity_id, ip_address, user_agent)
                 VALUES (:actor, :event, :entity_type, :entity_id, :ip, :agent)'
            );
            $statement->execute([
                'actor' => $actorId,
                'event' => $event,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'ip' => self::clientIp(),
                'agent' => isset($_SERVER['HTTP_USER_AGENT'])
                    ? substr((string) $_SERVER['HTTP_USER_AGENT'], 0, 255)
                    : null,
            ]);
            return true;
        } catch (Throwable $exception) {
            error_log('ClinicOS audit write failed: ' . $exception->getMessage());
            return false;
        }
    }

    private static function clientIp(): ?string
    {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : null;
    }
}
