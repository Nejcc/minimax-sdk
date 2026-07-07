<?php

declare(strict_types=1);

namespace Nejcc\Minimax\Resources;

final class Invoices extends Resource
{
    protected string $endpoint = 'issuedinvoices';

    /**
     * Run an action on an invoice (e.g. "IssueAndGeneratePdf", "Issue").
     * rowVersion is returned by create()/find() and guards against
     * concurrent edits.
     *
     * @return array<string, mixed>
     */
    public function action(int|string $id, string $action, string $rowVersion): array
    {
        return $this->client->request(
            'PUT',
            $this->path($id)."/actions/{$action}",
            null,
            ['rowVersion' => $rowVersion],
        );
    }

    /**
     * Issue the invoice and generate its PDF in one call.
     *
     * @return array<string, mixed>
     */
    public function issue(int|string $id, string $rowVersion): array
    {
        return $this->action($id, 'IssueAndGeneratePdf', $rowVersion);
    }

    /**
     * Issue the invoice and return the raw (decoded) PDF bytes.
     */
    public function pdf(int|string $id, string $rowVersion): string
    {
        $result = $this->issue($id, $rowVersion);

        return base64_decode($result['Data']['AttachmentData'] ?? '');
    }
}
