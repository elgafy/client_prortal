import { Form, Link, usePage } from '@inertiajs/react';
import {
    FolderKanban,
    LayoutGrid,
    LogOut,
    Settings,
    Wallet,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { logout } from '@/routes';

const links = [
    { title: 'Dashboard', href: '/portal/dashboard', icon: LayoutGrid },
    { title: 'Projects', href: '/portal/projects', icon: FolderKanban },
    { title: 'Payments', href: '/portal/payments', icon: Wallet },
    { title: 'Balance', href: '/portal/balance', icon: Wallet },
];

export function PortalNav() {
    const { auth } = usePage().props;

    return (
        <header className="border-b bg-card">
            <div className="mx-auto flex h-16 max-w-7xl items-center justify-between px-4">
                <div className="flex items-center gap-8">
                    <Link
                        href="/portal/dashboard"
                        className="flex items-center"
                    >
                        <AppLogo />
                    </Link>
                    <nav className="hidden items-center gap-1 sm:flex">
                        {links.map((link) => (
                            <Link
                                key={link.href}
                                href={link.href}
                                className="rounded-md px-3 py-2 text-sm font-medium text-muted-foreground hover:bg-accent hover:text-accent-foreground"
                            >
                                {link.title}
                            </Link>
                        ))}
                    </nav>
                </div>

                <div className="flex items-center gap-3">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href="/settings/profile">
                            <Settings className="size-4" />
                            Account Settings
                        </Link>
                    </Button>
                    <span className="hidden text-sm font-medium sm:inline">
                        {auth.user?.name}
                    </span>
                    <Avatar className="size-8">
                        <AvatarImage
                            src={auth.user?.avatar}
                            alt={auth.user?.name}
                        />
                        <AvatarFallback>
                            {auth.user?.name?.charAt(0).toUpperCase()}
                        </AvatarFallback>
                    </Avatar>
                    <Form method="post" action={logout()}>
                        {({ processing }) => (
                            <Button
                                variant="ghost"
                                size="icon"
                                disabled={processing}
                                title="Log out"
                            >
                                <LogOut className="size-4" />
                            </Button>
                        )}
                    </Form>
                </div>
            </div>
        </header>
    );
}
