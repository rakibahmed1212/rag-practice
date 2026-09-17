import { Head } from '@inertiajs/react';
import { FileText, HardDrive, Layers, MessageSquareText } from 'lucide-react';
import { useState } from 'react';
import PendingInvitationsModal from '@/components/pending-invitations-modal';
import { Badge } from '@/components/ui/badge';
import { Card, CardHeader, CardTitle } from '@/components/ui/card';
import { formatBytes } from '@/lib/utils';
import { dashboard } from '@/routes';
import type {
    DashboardInvitation,
    DashboardStats,
    DocumentStatus,
} from '@/types';

type Props = {
    pendingInvitations?: DashboardInvitation[];
    stats: DashboardStats;
};

const statusVariant: Record<
    DocumentStatus,
    'default' | 'secondary' | 'outline' | 'destructive'
> = {
    pending: 'outline',
    processing: 'secondary',
    completed: 'default',
    failed: 'destructive',
};

export default function Dashboard({ pendingInvitations = [], stats }: Props) {
    const [showInvitations, setShowInvitations] = useState(
        pendingInvitations.length > 0,
    );

    const statCards = [
        {
            label: 'Documents',
            value: stats.documentsTotal,
            icon: FileText,
            detail: `${stats.documentsCompleted} completed · ${stats.documentsProcessing} processing · ${stats.documentsFailed} failed`,
        },
        {
            label: 'Embeddings',
            value: stats.chunksTotal,
            icon: Layers,
            detail: 'chunks embedded across all documents',
        },
        {
            label: 'Storage used',
            value: formatBytes(stats.storageBytes),
            icon: HardDrive,
            detail: 'total size of uploaded PDFs',
        },
        {
            label: 'RAG queries',
            value: stats.queriesTotal,
            icon: MessageSquareText,
            detail: `${stats.queriesLast7Days} in the last 7 days`,
        },
    ];

    return (
        <>
            <Head title="Dashboard" />
            <PendingInvitationsModal
                invitations={pendingInvitations}
                open={pendingInvitations.length > 0 && showInvitations}
                onOpenChange={setShowInvitations}
            />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="grid auto-rows-min gap-4 md:grid-cols-4">
                    {statCards.map((card) => (
                        <Card key={card.label}>
                            <CardHeader>
                                <div className="flex items-center justify-between">
                                    <CardTitle className="text-sm font-medium text-muted-foreground">
                                        {card.label}
                                    </CardTitle>
                                    <card.icon className="size-4 text-muted-foreground" />
                                </div>
                                <p className="text-2xl font-semibold">
                                    {card.value}
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    {card.detail}
                                </p>
                            </CardHeader>
                        </Card>
                    ))}
                </div>

                <div className="grid flex-1 gap-4 md:grid-cols-2">
                    <div className="rounded-xl border p-4">
                        <h3 className="mb-3 text-sm font-medium">
                            Recent documents
                        </h3>
                        {stats.recentDocuments.length === 0 && (
                            <p className="text-sm text-muted-foreground">
                                No documents uploaded yet.
                            </p>
                        )}
                        <div className="space-y-2">
                            {stats.recentDocuments.map((document) => (
                                <div
                                    key={document.id}
                                    className="flex items-center justify-between gap-2"
                                >
                                    <span className="truncate text-sm">
                                        {document.title}
                                    </span>
                                    <Badge
                                        variant={statusVariant[document.status]}
                                    >
                                        {document.statusLabel}
                                    </Badge>
                                </div>
                            ))}
                        </div>
                    </div>

                    <div className="rounded-xl border p-4">
                        <h3 className="mb-3 text-sm font-medium">
                            Recent queries
                        </h3>
                        {stats.recentQueries.length === 0 && (
                            <p className="text-sm text-muted-foreground">
                                No queries asked yet.
                            </p>
                        )}
                        <div className="space-y-2">
                            {stats.recentQueries.map((query) => (
                                <div key={query.id} className="text-sm">
                                    <p className="truncate">{query.question}</p>
                                    <p className="text-xs text-muted-foreground">
                                        by {query.askedBy}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}

Dashboard.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: props.currentTeam ? dashboard(props.currentTeam.slug) : '/',
        },
    ],
});
