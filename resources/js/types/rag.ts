export type DocumentStatus = 'pending' | 'processing' | 'completed' | 'failed';

export type RagDocument = {
    id: number;
    title: string;
    originalFilename: string;
    status: DocumentStatus;
    statusLabel: string;
    error: string | null;
    chunkCount: number;
    size: number;
    uploadedBy: string;
    createdAt: string;
};

export type DocumentChunk = {
    id: number;
    chunkIndex: number;
    content: string;
};

export type DocumentDetail = {
    id: number;
    title: string;
    status: DocumentStatus;
    statusLabel: string;
    error: string | null;
    chunkCount: number;
};

export type RagQueryHistoryItem = {
    id: number;
    question: string;
    answer: string;
    askedBy: string;
    createdAt: string;
};

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

export type DashboardStats = {
    documentsTotal: number;
    documentsPending: number;
    documentsProcessing: number;
    documentsCompleted: number;
    documentsFailed: number;
    chunksTotal: number;
    storageBytes: number;
    queriesTotal: number;
    queriesLast7Days: number;
    recentDocuments: Array<{
        id: number;
        title: string;
        status: DocumentStatus;
        statusLabel: string;
        chunkCount: number;
        createdAt: string;
    }>;
    recentQueries: Array<{
        id: number;
        question: string;
        askedBy: string;
        createdAt: string;
    }>;
};
