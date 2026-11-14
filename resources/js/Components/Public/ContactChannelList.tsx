import type { ContactChannelDTO } from '../../Types/Support';
import ChannelIcon from './ChannelIcon';

/** Visible address for mail and phone links, so people can read or copy it. */
function addressOf(url: string): string | null {
    if (url.startsWith('mailto:')) {
        return url.slice(7);
    }
    return url.startsWith('tel:') ? url.slice(4) : null;
}

export default function ContactChannelList({ channels, compact = false }: { channels: ContactChannelDTO[]; compact?: boolean }) {
    if (channels.length === 0) {
        return null;
    }

    return (
        <ul className={`m-0 flex list-none flex-wrap p-0 ${compact ? 'gap-x-5 gap-y-1' : 'gap-x-10 gap-y-2'}`}>
            {channels.map((channel) => {
                const address = addressOf(channel.url);
                const external = channel.url.startsWith('https://');

                return (
                    <li key={channel.id}>
                        <a
                            href={channel.url}
                            {...(external ? { target: '_blank', rel: 'noopener noreferrer' } : {})}
                            className="inline-flex min-h-11 items-center gap-2.5 text-[15px] text-methaq-muted transition-colors hover:text-methaq-primary"
                        >
                            <ChannelIcon icon={channel.icon} />
                            <span>{channel.name}</span>
                            {address !== null && <bdi dir="ltr" className="text-methaq-primary">{address}</bdi>}
                        </a>
                    </li>
                );
            })}
        </ul>
    );
}
