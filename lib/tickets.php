<?php

declare(strict_types=1);

require_once __DIR__ . '/accounts.php';

function create_ticket(array $input, array $client): array
{
    return storage_update('tickets', function (&$tickets) use ($input, $client): array {
        $ticket = [
            'id' => new_id(), 'number' => 'AT-' . date('Y') . '-' . str_pad((string) (count($tickets) + 1), 4, '0', STR_PAD_LEFT),
            'client_id' => $client['id'], 'client_name' => $client['name'], 'client_email' => $client['email'],
            'development' => trim($input['development']), 'unit' => trim($input['unit'] ?? ''),
            'system' => trim($input['system']), 'priority' => trim($input['priority']), 'description' => trim($input['description']),
            'status' => 'open', 'created_at' => gmdate('c'),
        ];
        $tickets[] = $ticket;
        return $ticket;
    });
}

function tickets_for_client(string $id): array
{
    return array_values(array_filter(storage_read('tickets'), fn($ticket) => ($ticket['client_id'] ?? '') === $id));
}

function find_ticket(string $id): ?array
{
    foreach (storage_read('tickets') as $ticket) if (($ticket['id'] ?? '') === $id) return $ticket;
    return null;
}

function update_ticket_status(string $id, string $status): ?array
{
    return storage_update('tickets', function (&$tickets) use ($id, $status): ?array {
        foreach ($tickets as &$ticket) {
            if (($ticket['id'] ?? '') !== $id) continue;
            $ticket['status'] = $status;
            $ticket['updated_at'] = gmdate('c');
            if ($status === 'completed') $ticket['completed_at'] = gmdate('c');
            return $ticket;
        }
        return null;
    });
}

function delete_ticket(string $id): ?array
{
    return storage_update('tickets', function (&$tickets) use ($id): ?array {
        foreach ($tickets as $index => $ticket) {
            if (($ticket['id'] ?? '') !== $id) continue;
            array_splice($tickets, $index, 1);
            return $ticket;
        }
        return null;
    });
}
