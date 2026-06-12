export type SurfaceId = 'vestibular' | 'lingual' | 'palatino' | 'mesial' | 'distal' | 'oclusal' | 'incisal';

export type SurfaceStatus = 'sano' | 'caries' | 'restauracion';
export type ToothStatus = 'sano' | 'corona' | 'implante' | 'extraccion' | 'endodoncia';

export type ToolType = SurfaceStatus | ToothStatus | 'borrador' | 'seleccionar';

// Un diente tiene su estado global y el estado de sus superficies
export interface ToothSurfaces {
    vestibular: SurfaceStatus;
    lingual?: SurfaceStatus;  // Para inferiores
    palatino?: SurfaceStatus; // Para superiores
    mesial: SurfaceStatus;
    distal: SurfaceStatus;
    oclusal?: SurfaceStatus;  // Para molares/premolares
    incisal?: SurfaceStatus;  // Para incisivos/caninos
}

export interface ToothData {
    status: ToothStatus;
    surfaces: Partial<Record<SurfaceId, SurfaceStatus>>;
}

// Mapa de toda la dentadura
export type OdontogramState = Record<number, ToothData>;

export interface Evolution {
    date: string;
    description: string;
    data: OdontogramState;
}

// Constants for teeth numbering
export const UPPER_TEETH = [18, 17, 16, 15, 14, 13, 12, 11, 21, 22, 23, 24, 25, 26, 27, 28];
export const LOWER_TEETH = [48, 47, 46, 45, 44, 43, 42, 41, 31, 32, 33, 34, 35, 36, 37, 38];

export const COLORS = {
    sano: '#ffffff',
    caries: '#ef4444',
    restauracion: '#3b82f6',
    corona: '#eab308',
    implante: '#64748b',
    extraccion: '#ef4444',
    endodoncia: '#a855f7',
    hover: '#f1f5f9',
    borrador: '#000000',
    seleccionar: '#94a3b8'
};
