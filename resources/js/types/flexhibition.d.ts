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

/** Informazioni multilingua su un museo */
export interface MuseumData {
    readonly id: string;
    readonly name: Record<string, string>;
    readonly description: Record<string, string>;
    readonly logo: {
        url: Record<string, string>;
        title: Record<string, string>;
        description?: Record<string, string>;
    };
    readonly audio: {
        url: Record<string, string>;
        title: Record<string, string>;
        description?: Record<string, string>;
    }
    readonly images: Record<string, {
        url: Record<string, string>;
        title: Record<string, string>;
        description?: Record<string, string>;
    }>;
}

export interface MuseumUploadData extends MuseumData {
    readonly logo: {
        id: number | null;
        file?: Record<string, File>;
        url: Record<string, string>;
        title: Record<string, string>;
        description?: Record<string, string>;
    };

    readonly audio: {
        id: number | null;
        file?: Record<string, File>;
        url: Record<string, string>;
        title: Record<string, string>;
        description?: Record<string, string>;
    };

    readonly images: Record<string, {
        id: number | null;
        file?: Record<string, File>;
        url: Record<string, string>;
        title: Record<string, string>;
        description?: Record<string, string>;
    }>;
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
    readonly image: {
        url: Record<string, string>;
        title: Record<string, string>;
        description?: Record<string, string>;
    }[];
    readonly audio: {
        url: Record<string, string>;
        title: Record<string, string>;
        description?: Record<string, string>;
    };
    readonly start_date?: string;
    readonly end_date?: string;
    readonly is_archived: boolean;
    readonly museum_id: number;
    readonly museum_name: Record<string, string>;
}

/** Punto (dati base, riferimenti a media) */
export interface MuseumPoint {
    readonly museum_point_id: number;
    readonly museum_name: string;
    readonly museum_point_name: string;
    readonly museum_point_qr_url?: string;
    readonly museum_point_type: 'post' | 'exhibition';
}

/** Informazioni multilingua su un Punto */
export interface MuseumPointInfo {
    readonly museum_point_id: string;
    readonly museum_point_name: string;
    readonly museum_id?: string;
    readonly museum_name?: string;
    readonly museum_point_type: 'post' | 'exhibition';
    readonly linked_post_id: string;
    readonly linked_post_name: string;
    readonly linked_exhibition_id: string;
    readonly linked_exhibition_name: string;
    readonly museum_point_qr_url?: string;
    readonly museum_qr_code_image?: Record<
        string,
        {
            qr_code_title: string;
            qr_code_image_url: string;
        }
    >;
}

/** Opera*/
export interface Post {
    readonly exhibition_id?: string;
    readonly museum_point_id?: string;
    readonly post_id: number;
    readonly post_title_id: number;
    readonly post_content_id?: number;
    readonly post_image_id?: number;
    readonly post_audio_id?: number;
}

/** Informazioni multilingua su un' Opera */
export interface PostInfo {
    readonly exhibition_id: string;
    readonly exhibition_name: string;
    readonly post_id: string;
    readonly post_title: string;
    readonly post_content?: string;
    readonly post_gallery: Record<
        string,
        {
            media_url: string;
            media_title: string;
            media_description?: string;
        }
    >;
    readonly post_audio: Record<
        string,
        {
            media_url: string;
            media_title: string;
            media_description?: string;
        }
    >;
    readonly museum_point_id?: string;
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
