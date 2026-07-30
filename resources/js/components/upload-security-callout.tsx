import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { cn } from '@/lib/utils';
import { ChevronDown, Shield } from 'lucide-react';
import { useState } from 'react';

export type ExtensionUploadSecurity = {
    zipMaxMb: number;
    zipMaxEntries: number;
    zipMaxUncompressedMb: number;
    scanEnabled: boolean;
};

export type MediaUploadSecurity = {
    maxMb: number;
    scanEnabled: boolean;
    svgAllowed: boolean;
};

export function UploadSecurityExtensionCallout({ data }: { data: ExtensionUploadSecurity }) {
    const [open, setOpen] = useState(false);

    return (
        <Collapsible open={open} onOpenChange={setOpen} className="border-border/80 bg-muted/20 rounded-lg border">
            <div className="flex gap-3 px-3 py-2.5 sm:items-center sm:justify-between sm:gap-4">
                <div className="flex min-w-0 flex-1 gap-2.5">
                    <Shield className="text-muted-foreground mt-0.5 h-4 w-4 shrink-0" aria-hidden />
                    <p className="text-muted-foreground text-sm leading-snug">
                        <span className="text-foreground font-medium">Unggah aman.</span> Tema dan plugin menjalankan kode PHP di server — pasang
                        hanya dari sumber tepercaya.
                    </p>
                </div>
                <CollapsibleTrigger
                    type="button"
                    className={cn(
                        'text-primary inline-flex shrink-0 items-center gap-1 rounded-md px-2 py-1 text-xs font-medium',
                        'hover:bg-muted/80 focus-visible:ring-ring focus-visible:ring-2 focus-visible:outline-none',
                    )}
                >
                    Detail
                    <ChevronDown className={cn('h-3.5 w-3.5 transition-transform duration-200', open && 'rotate-180')} />
                </CollapsibleTrigger>
            </div>
            <CollapsibleContent>
                <div className="border-border/60 text-muted-foreground space-y-2 border-t px-3 pt-2 pb-3 text-xs sm:pl-8.5">
                    <p>
                        ZIP divalidasi: struktur, path traversal, maks. {data.zipMaxMb}&nbsp;MB per berkas, {data.zipMaxEntries} entri, ~
                        {data.zipMaxUncompressedMb}&nbsp;MB total tidak terkompresi.
                    </p>
                    <p className="text-muted-foreground/90">
                        Pemindaian: {data.scanEnabled ? 'ClamAV aktif (server).' : 'ClamAV nonaktif — atur di .env jika perlu.'}
                    </p>
                </div>
            </CollapsibleContent>
        </Collapsible>
    );
}

export function UploadSecurityMediaCallout({ data }: { data: MediaUploadSecurity }) {
    const [open, setOpen] = useState(false);

    return (
        <Collapsible open={open} onOpenChange={setOpen} className="border-border/80 bg-muted/20 rounded-lg border">
            <div className="flex gap-3 px-3 py-2.5 sm:items-center sm:justify-between sm:gap-4">
                <div className="flex min-w-0 flex-1 gap-2.5">
                    <Shield className="text-muted-foreground mt-0.5 h-4 w-4 shrink-0" aria-hidden />
                    <p className="text-muted-foreground text-sm leading-snug">
                        <span className="text-foreground font-medium">Media.</span> Hanya tipe file yang diizinkan; maks. {data.maxMb}&nbsp;MB per
                        berkas.
                        {!data.svgAllowed && ' SVG dimatikan secara bawaan.'}
                    </p>
                </div>
                <CollapsibleTrigger
                    type="button"
                    className={cn(
                        'text-primary inline-flex shrink-0 items-center gap-1 rounded-md px-2 py-1 text-xs font-medium',
                        'hover:bg-muted/80 focus-visible:ring-ring focus-visible:ring-2 focus-visible:outline-none',
                    )}
                >
                    Detail
                    <ChevronDown className={cn('h-3.5 w-3.5 transition-transform duration-200', open && 'rotate-180')} />
                </CollapsibleTrigger>
            </div>
            <CollapsibleContent>
                <div className="border-border/60 text-muted-foreground space-y-2 border-t px-3 pt-2 pb-3 text-xs sm:pl-8.5">
                    {data.svgAllowed && <p>SVG diizinkan — hati-hati jika konten dari luar.</p>}
                    <p>Pemindaian: {data.scanEnabled ? 'ClamAV dapat diaktifkan via .env.' : 'ClamAV nonaktif — set di .env jika tersedia.'}</p>
                    <p className="leading-relaxed">
                        Apache: <code className="bg-muted rounded px-1 py-0.5 font-mono text-[0.7rem]">storage/app/public/.htaccess</code>
                        {' · '}
                        Nginx:{' '}
                        <code className="bg-muted rounded px-1 py-0.5 font-mono text-[0.7rem]">deploy/nginx-storage-hardening.conf.example</code>
                    </p>
                </div>
            </CollapsibleContent>
        </Collapsible>
    );
}
