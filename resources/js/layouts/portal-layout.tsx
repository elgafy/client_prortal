import { PortalNav } from '@/components/portal/portal-nav';

export default function PortalLayout({
    children,
}: {
    children: React.ReactNode;
}) {
    return (
        <div className="flex min-h-screen flex-col bg-background">
            <PortalNav />
            <main className="mx-auto w-full max-w-7xl flex-1 px-4 py-6">
                {children}
            </main>
            <footer className="border-t py-4 text-center text-xs text-muted-foreground">
                Gafy Studio Clients Portal
            </footer>
        </div>
    );
}
