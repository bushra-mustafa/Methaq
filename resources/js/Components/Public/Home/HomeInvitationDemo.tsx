import { useMemo } from "react";

import { InvitationPlayer } from "../../../Domains/Editor/Components/InvitationPlayer";

import type { EditorAsset } from "../../../Types/Editor";
import type { HexColor, Palette } from "../../../Types/Palette";
import type {
    EnvelopeAppearance,
    SceneConfig,
} from "../../../Types/SceneConfig";

interface HomeInvitationDemoProps {
    cardUrl: string;
    viewport?: "phone" | "desktop";
    envelopeStyle?: EnvelopeAppearance["style"];
    sealStyle?: EnvelopeAppearance["sealStyle"];
    sealColor?: HexColor;
    className?: string;
}

const demoAssets: EditorAsset[] = [];

const demoPalette: Palette = {
    schemaVersion: 1,
    values: {
        background: "#F6FBF2",
        surface: "#FDFBF7",
        primaryText: "#074B36",
        secondaryText: "#435443",
        accent: "#B69A50",
        effect: "#E6D28B",
    },
};

export default function HomeInvitationDemo({
    cardUrl,
    viewport = "desktop",
    envelopeStyle = "classic",
    sealStyle = "wax",
    sealColor = "#B69A50",
    className = "",
}: HomeInvitationDemoProps) {
    const scene = useMemo<SceneConfig>(
        () => ({
            sceneSchemaVersion: 1,

            opening: {
                type: "envelope",
                durationMs: 2100,
                envelope: {
                    presetId: "classic-fold",
                    presetVersion: 1,

                    appearance: {
                        style: envelopeStyle,
                        sealStyle,
                        sealX: 50,
                        sealY: 52,
                        sealSize: 20,
                    },

                    paperColor: {
                        source: "literal",
                        value: "#FDFBF7",
                    },

                    liningColor: {
                        source: "literal",
                        value: "#F0F5ED",
                    },

                    sealColor: {
                        source: "literal",
                        value: sealColor,
                    },

                    monogram: "م",
                },
            },

            cover: {
                heading: "دعوة زفاف",
                names: "ليلى وعمر",
                dateLabel: "موعد تجريبي",
                message: "بحضوركم تكتمل فرحتنا",
                language: "ar",
                decoration: "halo",
                animateText: false,
            },

            backdrop: {
                preset: "inherit",
            },

            effects: [],

            audio: {
                enabled: false,
                asset: null,
                volume: 0,
            },

            motionPolicy: "system",
        }),
        [envelopeStyle, sealStyle, sealColor],
    );

    return (
        <div className={className}>
            <InvitationPlayer
                key={`${envelopeStyle}-${sealStyle}-${sealColor}`}
                assets={demoAssets}
                scene={scene}
                palette={demoPalette}
                cardUrl={cardUrl}
                title="دعوة ميثاق التجريبية"
                viewport={viewport}
                audioAllowed={false}
                manageFocus={false}
            />
        </div>
    );
}
