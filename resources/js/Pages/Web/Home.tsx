import { Head, usePage } from "@inertiajs/react";
import PublicLayout from "../../Layouts/PublicLayout";
import type { SharedPageProps } from "../../Types/PageProps";
import type { LibraryTemplate } from "../../Types/Library";
import HomeHero from "../../Components/Public/Home/HomeHero";
import HomeTemplates from "../../Components/Public/Home/HomeTemplates";
import HomeSteps from "../../Components/Public/Home/HomeSteps";
import HomeEnvelopeSpotlight from "../../Components/Public/Home/HomeEnvelopeSpotlight";
import HomeFeatures from "../../Components/Public/Home/HomeFeatures";
import HomeClosing from "../../Components/Public/Home/HomeClosing";

interface HomeProps {
    showPrototype: boolean;
    featuredTemplates: LibraryTemplate[];
}

export default function Home({ showPrototype, featuredTemplates }: HomeProps) {
    const { auth } = usePage<SharedPageProps>().props;
    const isAuthenticated = Boolean(auth.user);

    return (
        <PublicLayout>
            <Head title="عهد الفرح" />
            <HomeHero
                isAuthenticated={isAuthenticated}
                showPrototype={showPrototype}
            />
            <HomeTemplates
                templates={featuredTemplates}
                isAuthenticated={isAuthenticated}
            />
            <HomeSteps />
            <HomeEnvelopeSpotlight />
            <HomeFeatures />
            <HomeClosing isAuthenticated={isAuthenticated} />
        </PublicLayout>
    );
}
