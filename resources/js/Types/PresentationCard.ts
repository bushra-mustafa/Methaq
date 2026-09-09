export interface PresentationCardPart { url: string; x: number; y: number; width: number; height: number; text: boolean }
export interface PresentationCard { width: number; height: number; background: string; parts: PresentationCardPart[] }
