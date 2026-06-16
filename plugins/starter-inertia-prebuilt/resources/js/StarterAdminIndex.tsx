import { Head, Link } from '@inertiajs/react';
import { ArrowRight, Package, Puzzle, Rocket, Wrench } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

interface Props {
    plugin: {
        name: string;
        slug: string;
        version: string;
    };
    features: string[];
    fileMap: string[];
}

export default function StarterAdminIndex({ plugin, features, fileMap }: Props) {
    const breadcrumbs = [
        { title: 'Dashboard', href: route('admin.dashboard') },
        { title: 'Starter Plugin' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Starter Inertia Prebuilt" />

            <div className="space-y-6 p-6">
                <div className="flex flex-col gap-4 rounded-2xl border bg-card p-6 shadow-sm lg:flex-row lg:items-center lg:justify-between">
                    <div className="space-y-3">
                        <div className="flex items-center gap-3">
                            <div className="rounded-xl bg-primary/10 p-3 text-primary">
                                <Rocket className="h-6 w-6" />
                            </div>
                            <div>
                                <h1 className="text-2xl font-semibold tracking-tight">{plugin.name}</h1>
                                <p className="text-sm text-muted-foreground">
                                    Starter plugin untuk admin page Inertia prebuilt yang siap dipaketkan ke shared hosting.
                                </p>
                            </div>
                        </div>

                        <div className="flex flex-wrap gap-2">
                            <Badge variant="secondary">Slug: {plugin.slug}</Badge>
                            <Badge>v{plugin.version}</Badge>
                            <Badge variant="outline">inertia-prebuilt</Badge>
                        </div>
                    </div>

                    <Link href={route('admin.plugins.index')}>
                        <Button variant="outline">
                            Lihat Plugin Manager
                            <ArrowRight className="ml-2 h-4 w-4" />
                        </Button>
                    </Link>
                </div>

                <div className="grid gap-6 xl:grid-cols-[minmax(0,1.3fr)_minmax(320px,0.7fr)]">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Puzzle className="h-5 w-5 text-primary" />
                                Yang Ditunjukkan Starter Ini
                            </CardTitle>
                            <CardDescription>
                                Gunakan struktur ini sebagai baseline saat membuat plugin admin baru berbasis React/Inertia.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {features.map((feature) => (
                                <div key={feature} className="rounded-lg border border-dashed px-4 py-3 text-sm">
                                    {feature}
                                </div>
                            ))}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Package className="h-5 w-5 text-primary" />
                                File Penting
                            </CardTitle>
                            <CardDescription>
                                Ini file minimum yang perlu ada agar plugin prebuilt bisa di-scan, diaktifkan, dan dirender.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            {fileMap.map((file) => (
                                <div key={file} className="rounded-md bg-muted px-3 py-2 font-mono text-xs">
                                    {file}
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Wrench className="h-5 w-5 text-primary" />
                            Langkah Clone Menjadi Plugin Baru
                        </CardTitle>
                        <CardDescription>
                            Ubah slug, namespace, route, judul halaman, lalu rebuild dist plugin sebelum di-zip.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <ol className="list-decimal space-y-2 pl-5 text-sm text-muted-foreground">
                            <li>Copy folder plugin ini ke slug baru.</li>
                            <li>Ganti nama plugin dan mapping komponen di plugin.json.</li>
                            <li>Sesuaikan controller PHP dan route admin plugin.</li>
                            <li>Ubah UI React di halaman starter ini.</li>
                            <li>Jalankan build prebuilt lokal, lalu paketkan folder plugin beserta dist.</li>
                        </ol>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
