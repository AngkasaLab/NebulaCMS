import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { sanitizeLinkHref } from '@/utils/urlValidator';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    initialHref?: string;
    onSubmit: (href: string) => void;
    onUnset?: () => void;
};

export default function LinkDialog({ open, onOpenChange, initialHref, onSubmit, onUnset }: Props) {
    const [value, setValue] = useState(initialHref ?? '');
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        if (!open) {
            return;
        }
        setValue(initialHref ?? '');
        setError(null);
    }, [open, initialHref]);

    const handleSave = () => {
        const sanitized = sanitizeLinkHref(value);
        if (!sanitized) {
            setError('URL tidak valid. Gunakan http(s), mailto, tel, atau path relatif (/...)');
            return;
        }
        onSubmit(sanitized);
        onOpenChange(false);
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Link</DialogTitle>
                </DialogHeader>

                <div className="space-y-2">
                    <Label htmlFor="link-href">URL</Label>
                    <Input
                        id="link-href"
                        value={value}
                        onChange={(e) => setValue(e.target.value)}
                        placeholder="https://example.com atau /halaman"
                        autoFocus
                    />
                    {error && <div className="text-sm text-destructive">{error}</div>}
                </div>

                <DialogFooter className="gap-2 sm:gap-0">
                    {onUnset && initialHref && (
                        <Button
                            type="button"
                            variant="destructive"
                            onClick={() => {
                                onUnset();
                                onOpenChange(false);
                            }}
                        >
                            Remove
                        </Button>
                    )}
                    <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
                        Cancel
                    </Button>
                    <Button type="button" onClick={handleSave}>
                        Save
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

