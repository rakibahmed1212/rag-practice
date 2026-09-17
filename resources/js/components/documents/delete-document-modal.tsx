import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { destroy } from '@/routes/documents';
import type { RagDocument } from '@/types';

type Props = {
    document: RagDocument | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

export default function DeleteDocumentModal({
    document,
    open,
    onOpenChange,
}: Props) {
    const [processing, setProcessing] = useState(false);
    const { currentTeam } = usePage().props;

    const deleteDocument = () => {
        if (!document || !currentTeam) {
            return;
        }

        router.delete(destroy([currentTeam.slug, document.id]), {
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
            onSuccess: () => onOpenChange(false),
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete document</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete{' '}
                        <strong>{document?.title}</strong>? Its{' '}
                        {document?.chunkCount ?? 0} embedded chunks will also be
                        removed.
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter className="gap-2">
                    <DialogClose asChild>
                        <Button variant="secondary">Cancel</Button>
                    </DialogClose>

                    <Button
                        variant="destructive"
                        disabled={processing}
                        onClick={deleteDocument}
                    >
                        Delete document
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
