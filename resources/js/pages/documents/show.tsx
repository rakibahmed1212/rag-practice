import { Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { index as documentsIndex } from '@/routes/documents';
import type { DocumentChunk, DocumentDetail, Paginated } from '@/types';

type Props = {
    document: DocumentDetail;
    chunks: Paginated<DocumentChunk>;
};

const statusVariant: Record<
    DocumentDetail['status'],
    'default' | 'secondary' | 'outline' | 'destructive'
> = {
    pending: 'outline',
    processing: 'secondary',
    completed: 'default',
    failed: 'destructive',
};

export default function DocumentShow({ document, chunks }: Props) {
    return (
        <>
            <Head title={document.title} />

            <div className="flex flex-col space-y-6 p-4">
                <div className="flex items-center gap-3">
                    <Heading variant="small" title={document.title} />
                    <Badge variant={statusVariant[document.status]}>
                        {document.statusLabel}
                    </Badge>
                </div>

                {document.error && (
                    <p className="text-sm text-destructive">{document.error}</p>
                )}

                <p className="text-sm text-muted-foreground">
                    {document.chunkCount} embedded chunks
                </p>

                <div className="space-y-3">
                    {chunks.data.map((chunk) => (
                        <div key={chunk.id} className="rounded-lg border p-4">
                            <p className="mb-2 text-xs font-medium text-muted-foreground">
                                Chunk #{chunk.chunkIndex + 1}
                            </p>
                            <p className="text-sm whitespace-pre-wrap">
                                {chunk.content}
                            </p>
                        </div>
                    ))}
                </div>

                {(chunks.prev_page_url || chunks.next_page_url) && (
                    <div className="flex justify-end gap-2">
                        {chunks.prev_page_url && (
                            <Button variant="outline" size="sm" asChild>
                                <Link href={chunks.prev_page_url}>
                                    Previous
                                </Link>
                            </Button>
                        )}
                        {chunks.next_page_url && (
                            <Button variant="outline" size="sm" asChild>
                                <Link href={chunks.next_page_url}>Next</Link>
                            </Button>
                        )}
                    </div>
                )}
            </div>
        </>
    );
}

DocumentShow.layout = (props: {
    document: DocumentDetail;
    currentTeam?: { slug: string } | null;
}) => ({
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
        {
            title: props.document.title,
            href: '#',
        },
    ],
});
