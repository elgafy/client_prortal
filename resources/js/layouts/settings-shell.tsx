import { usePage } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import AppLayout from '@/layouts/app-layout';
import PortalLayout from '@/layouts/portal-layout';
import SettingsLayout from '@/layouts/settings/layout';

/**
 * Settings render inside the internal shell for administrators/staff and
 * inside the portal shell for client users.
 */
export default function SettingsShell({ children }: PropsWithChildren) {
    const { auth } = usePage().props;

    const Shell = auth.user?.role === 'client' ? PortalLayout : AppLayout;

    return (
        <Shell>
            <SettingsLayout>{children}</SettingsLayout>
        </Shell>
    );
}
