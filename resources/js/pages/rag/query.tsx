import { Form, Head, usePage } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { dashboard } from '@/routes';
import { index as ragQueryIndex, store } from '@/routes/rag-query';
import type { RagQueryHistoryItem } from '@/types';

type Props = {
    history: RagQueryHistoryItem[];
};

export default function RagQuery({ history }: Props) {
    const { currentTeam } = usePage().props;
    const latest = history[0] ?? null;
    const previous = history.slice(1);

    if (!currentTeam) {
        return null;
    }

    return (
        <>
            <Head title="RAG Query" />

            <div className="flex flex-col space-y-6 p-4">
                <Heading
                    variant="small"
                    title="RAG Query"
                    description="Ask a question against your team's embedded documents."
                />

                <Form
                    {...store.form(currentTeam.slug)}
                    resetOnSuccess={['question']}
                    className="space-y-3"
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="question">Question</Label>
                                <Textarea
                                    id="question"
                                    name="question"
                                    placeholder="What does this document say about..."
                                    required
                                    minLength={3}
                                />
                                {errors.question && (
                                    <p className="text-sm text-destructive">
                                        {errors.question}
                                    </p>
                                )}
                            </div>

                            <Button type="submit" disabled={processing}>
                                {processing ? 'Asking...' : 'Ask'}
                            </Button>
                        </>
                    )}
                </Form>

                {latest && (
                    <div className="space-y-2 rounded-lg border p-4">
                        <p className="text-sm font-medium">{latest.question}</p>
                        <p className="text-sm whitespace-pre-wrap text-muted-foreground">
                            {latest.answer}
                        </p>
                    </div>
                )}

                {previous.length > 0 && (
                    <div className="space-y-3">
                        <h3 className="text-sm font-medium text-muted-foreground">
                            History
                        </h3>
                        {previous.map((item) => (
                            <div
                                key={item.id}
                                className="space-y-1 rounded-lg border p-4"
                            >
                                <p className="text-sm font-medium">
                                    {item.question}
                                </p>
                                <p className="text-sm whitespace-pre-wrap text-muted-foreground">
                                    {item.answer}
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    Asked by {item.askedBy}
                                </p>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

RagQuery.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: props.currentTeam ? dashboard(props.currentTeam.slug) : '/',
        },
        {
            title: 'RAG Query',
            href: props.currentTeam
                ? ragQueryIndex(props.currentTeam.slug)
                : '/',
        },
    ],
});
