type LanguageCode = `${string}${string}`;

/** Lingua supportata dal sistema */
export interface Language {
    readonly id: number;
    readonly name: string;
    readonly code: LanguageCode;
};

// Per file singoli con o senza informazioni multilingua (es. titolo, didascalia)
export interface MediaData extends Record<string, any> {
    id: number | null;
    file: File | null;
    url: string | null;
    title?: string | Record<string, string>;
    caption?: string | Record<string, string>;
}

// Per file multipli diversi per lingua (es. Audio)
export type MediaDataLocalized = Record<LanguageCode, MediaData>;

export interface MuseumMinimalData {
    readonly id: number;
    readonly name: Record<string, string>;
}

export interface MuseumData extends MuseumMinimalData {
    readonly description: Record<string, string>;
    readonly logo: MediaData;
    readonly audio: MediaDataLocalized;
    readonly images: MediaData[];
}

export interface ExhibitionMinimalData {
    readonly id: number;
    readonly name: Record<string, string>;
}

export interface ExhibitionData extends ExhibitionMinimalData {
    readonly description: Record<string, string>;
    readonly images: MediaData[];
    readonly audio: MediaDataLocalized;
    readonly start_date?: string;
    readonly end_date?: string;
    readonly is_archived: boolean;
    readonly museum_id: number;
}

export interface PostData {
    readonly id: number;
    readonly name: Record<string, string>;
    readonly description?: Record<string, string>;
    readonly content?: Record<string, string>;
    readonly images: MediaData[];
    readonly audio: MediaDataLocalized;
    readonly exhibition_id: number;
}

type RouteFunction = (...args: any[]) => { url: string; method: string };

type ResourceRoutes = {
    show: RouteFunction;
    edit: RouteFunction;
    index: RouteFunction;
    create: RouteFunction;
    store: RouteFunction;
    update: RouteFunction;
    destroy: RouteFunction;
};
