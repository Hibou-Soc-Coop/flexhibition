type LanguageCode = `${string}${string}`;

/** Lingua supportata dal sistema */
export type Language = {
    readonly id: number;
    readonly name: string;
    readonly code: LanguageCode;
};

/** Media generico (immagine, video, audio, documento) */
export interface Media {
    readonly media_id: number;
    readonly media_language_id: number;
    readonly media_url: string;
    readonly media_type: 'image' | 'video' | 'audio' | 'document';
    readonly media_title: string;
    readonly media_description?: string;
}

/** Informazioni multilingua su un media */
export interface MediaInfo {
    readonly media_id: string;
    readonly media_language_id: number;
    readonly media_title_id: string;
    readonly media_description_id: string;
    readonly media_url: string;
    readonly media_type: 'image' | 'video' | 'audio' | 'document';
    readonly media_contents: Record<
        string,
        {
            media_title: string;
            media_description?: string;
            media_url: string;
        }
    >;
}

/** Museo (dati base, riferimenti a media) */
export interface MuseumRecord {
    readonly id: number;
    readonly name: string;
    readonly description: string;
    readonly logo_id?: number;
    readonly audio_id?: number;
}

/** Spatie Media Library Object */
export interface SpatieMedia {
    id: number;
    model_type: string;
    model_id: number;
    uuid: string;
    collection_name: string;
    name: string;
    file_name: string;
    mime_type: string;
    disk: string;
    conversions_disk: string;
    size: number;
    manipulations: any[];
    custom_properties: {
        lang?: string;
        title?: string;
        description?: string;
        group_index?: number;
        [key: string]: any;
    };
    generated_conversions: Record<string, boolean>;
    responsive_images: any[];
    order_column: number;
    original_url: string;
    preview_url: string;
}

/** Informazioni multilingua su un museo */
export interface MuseumData {
    readonly id: number;
    readonly name: Record<string, string>;
    readonly description: Record<string, string>;
    readonly logo: SpatieMedia[];
    readonly audio: SpatieMedia[];
    readonly images: SpatieMedia[];
}

export interface MuseumUploadData extends MuseumData {
    readonly logo: any; // Relaxed for upload form
    readonly audio: any;
    readonly images: any;
}

/** Mostra (dati base, riferimenti a media) */
export interface ExhibitionRecord {
    readonly id: number;
    readonly name: string;
    readonly description?: string;
    readonly image_id?: number[];
    readonly audio_id?: number;
    readonly start_date?: string;
    readonly end_date?: string;
    readonly is_archived: boolean;
    readonly museum_id: number;
}

/** Informazioni multilingua su una mostra */
export interface ExhibitionData {
    readonly id: number;
    readonly name: Record<string, string>;
    readonly description?: Record<string, string>;
    readonly images: Record<string, {
        id: number;
        url: Record<string, string>;
        title: Record<string, string>;
        description?: Record<string, string>;
    }>;
    readonly audio: {
        url: Record<string, string>;
        title: Record<string, string>;
        description?: Record<string, string>;
    };
    readonly start_date?: string;
    readonly end_date?: string;
    readonly is_archived: boolean;
    readonly museum_id: number;
    readonly museum_name?: Record<string, string>;
}


/** Opera*/
export interface PostRecord {
    readonly id: number;
    readonly name: number;
    readonly description?: number;
    readonly image_id?: number;
    readonly audio_id?: number;
    readonly exhibition_id?: number;
}

/** Informazioni multilingua su un' Opera */
export interface PostData {
    readonly id: number;
    readonly name: Record<string, string>;
    readonly description?: Record<string, string>;
    readonly content?: Record<string, string>;
    readonly images: Record<
        string,
        {
            id: number;
            url: Record<string, string>;
            title: Record<string, string>;
            description?: Record<string, string>;
        }
    >;
    readonly audio: {
        url: Record<string, string>;
        title: Record<string, string>;
        description?: Record<string, string>;
    };
    readonly exhibition_id: number;
    readonly exhibition_name?: Record<string, string>;
}

export interface Post {
    readonly id: number;
    readonly name: Record<string, string>;
    readonly description?: Record<string, string>;
    readonly content?: Record<string, string>;
    readonly images: Record<string, string>[] | null;
    readonly audio: Record<string, string> | null;
    readonly exhibition_id: number;
    readonly museum_id: number;
}

/** Opera della mostra */
export interface ExhibitionPost {
    readonly exhibition_id: number;
    readonly exhibition_post_id: number;
    readonly museum_point_id?: number;
}

/** Informazioni multilingua su un' Opera */
export interface ExhibitionPostInfo {
    readonly exhibition_id: string;
    readonly exhibition_post_id: string;
    readonly museum_point_id?: string;
}

export interface MediaData extends Record<string, any> {
    id: number | null;
    file?: Record<string, File>;
    url?: Record<string, string> | null;
    title: Record<string, string>;
    description?: Record<string, string>;
    to_delete?: boolean;
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
