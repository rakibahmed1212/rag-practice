import { Form, usePage } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { store } from '@/routes/documents';

export default function UploadDocumentModal({ children }: PropsWithChildren) {
    const [open, setOpen] = useState(false);
    const { currentTeam } = usePage().props;

    if (!currentTeam) {
        return null;
    }

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{children}</DialogTrigger>
            <DialogContent>
                <Form
                    key={String(open)}
                    {...store.form(currentTeam.slug)}
                    className="space-y-6"
                    onSuccess={() => setOpen(false)}
                >
                    {({ errors, processing, progress }) => (
                        <>
                            <DialogHeader>
                                <DialogTitle>Upload a PDF</DialogTitle>
                                <DialogDescription>
                                    The document will be chunked and embedded in
                                    the background so it can be used for RAG
                                    queries.
                                </DialogDescription>
                            </DialogHeader>

                            <div className="grid gap-2">
                                <Label htmlFor="file">PDF file</Label>
                                <Input
                                    id="file"
                                    name="file"
                                    type="file"
                                    accept="application/pdf,.pdf"
                                    required
                                />
                                <InputError message={errors.file} />
                                {progress && (
                                    <div className="h-1.5 w-full overflow-hidden rounded-full bg-muted">
                                        <div
                                            className="h-full bg-primary transition-all"
                                            style={{
                                                width: `${progress.percentage}%`,
                                            }}
                                        />
                                    </div>
                                )}
                            </div>

                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button variant="secondary">Cancel</Button>
                                </DialogClose>

                                <Button type="submit" disabled={processing}>
                                    Upload
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
