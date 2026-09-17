import { Head, Link, usePage } from '@inertiajs/react';
import { Eye, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import DeleteDocumentModal from '@/components/documents/delete-document-modal';
import UploadDocumentModal from '@/components/documents/upload-document-modal';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatBytes } from '@/lib/utils';
import { dashboard } from '@/routes';
import { index as documentsIndex, show } from '@/routes/documents';
import type { Paginated, RagDocument } from '@/types';

type Props = {
    documents: Paginated<RagDocument>;
};

const statusVariant: Record<
    RagDocument['status'],
    'default' | 'secondary' | 'outline' | 'destructive'
> = {
    pending: 'outline',
    processing: 'secondary',
    completed: 'default',
    failed: 'destructive',
};

export default function DocumentsIndex({ documents }: Props) {
    const { currentTeam } = usePage().props;
    const [deleting, setDeleting] = useState<RagDocument | null>(null);

    return (
        <>
            <Head title="Documents" />

            <div className="flex flex-col space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        variant="small"
                        title="Documents"
                        description="Upload PDFs to embed them for RAG queries."
                    />

                    <UploadDocumentModal>
                        <Button>
                            <Plus /> Upload PDF
                        </Button>
                    </UploadDocumentModal>
                </div>

                <div className="space-y-3">
                    {documents.data.length === 0 && (
                        <p className="text-sm text-muted-foreground">
                            No documents uploaded yet.
                        </p>
                    )}

                    {documents.data.map((document) => (
                        <div
                            key={document.id}
                            className="flex items-center justify-between gap-4 rounded-lg border p-4"
                        >
                            <div className="min-w-0">
                                <div className="flex items-center gap-2">
                                    <span className="truncate font-medium">
                                        {document.title}
                                    </span>
                                    <Badge
                                        variant={statusVariant[document.status]}
                                    >
                                        {document.statusLabel}
                                    </Badge>
                                </div>
                                <span className="text-sm text-muted-foreground">
                                    {document.chunkCount} chunks &middot;{' '}
                                    {formatBytes(document.size)} &middot;
                                    uploaded by {document.uploadedBy}
                                </span>
                                {document.error && (
                                    <p className="mt-1 text-sm text-destructive">
                                        {document.error}
                                    </p>
                                )}
                            </div>

                            <div className="flex items-center gap-2">
                                {currentTeam && (
                                    <Button
                                        variant="outline"
                                        size="icon"
                                        asChild
                                    >
                                        <Link
                                            href={show([
                                                currentTeam.slug,
                                                document.id,
                                            ])}
                                        >
                                            <Eye />
                                        </Link>
                                    </Button>
                                )}
                                <Button
                                    variant="outline"
                                    size="icon"
                                    onClick={() => setDeleting(document)}
                                >
                                    <Trash2 />
                                </Button>
                            </div>
                        </div>
                    ))}
                </div>

                {(documents.prev_page_url || documents.next_page_url) && (
                    <div className="flex justify-end gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={!documents.prev_page_url}
                            asChild={!!documents.prev_page_url}
                        >
                            {documents.prev_page_url ? (
                                <Link href={documents.prev_page_url}>
                                    Previous
                                </Link>
                            ) : (
                                <span>Previous</span>
                            )}
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={!documents.next_page_url}
                            asChild={!!documents.next_page_url}
                        >
                            {documents.next_page_url ? (
                                <Link href={documents.next_page_url}>Next</Link>
                            ) : (
                                <span>Next</span>
                            )}
                        </Button>
                    </div>
                )}
            </div>

            <DeleteDocumentModal
                document={deleting}
                open={deleting !== null}
                onOpenChange={(open) => !open && setDeleting(null)}
            />
        </>
    );
}

DocumentsIndex.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: props.currentTeam ? dashboard(props.currentTeam.slug) : '/',
        },
        {
            title: 'Documents',
            href: props.currentTeam
                ? documentsIndex(props.currentTeam.slug)
                : '/',
        },
    ],
});
