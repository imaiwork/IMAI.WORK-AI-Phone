export type PlayKey = 'face' | 'dance' | 'clip'

export interface PlayTemplate {
    ref_id: string
    name: string
    video_url: string
    cover_url?: string
    duration_sec?: number
    heat?: string
    category?: string
    face_count?: number
    allow_bg_replace?: boolean
    script?: { role?: string; name?: string; text: string }[]
}

export interface PlayLine {
    role: string
    text: string
}

export interface PlayPersona {
    id: number
    persona_name: string
    industry?: string
    main_business?: string
    quick_desc?: string
    conversion_hook?: string
}
