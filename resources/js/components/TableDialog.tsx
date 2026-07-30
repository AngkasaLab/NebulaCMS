import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useState, useEffect } from 'react';

interface TableDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onInsert: (rows: number, cols: number, withHeaderRow: boolean) => void;
}

export default function TableDialog({ open, onOpenChange, onInsert }: TableDialogProps) {
    const [rows, setRows] = useState(3);
    const [cols, setCols] = useState(3);
    const [withHeaderRow, setWithHeaderRow] = useState(true);

    useEffect(() => {
        if (open) {
            setRows(3);
            setCols(3);
            setWithHeaderRow(true);
        }
    }, [open]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        onInsert(Math.max(1, rows), Math.max(1, cols), withHeaderRow);
        onOpenChange(false);
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>Insert Table</DialogTitle>
                        <DialogDescription>Specify the number of rows and columns.</DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="table-rows">Rows</Label>
                                <Input
                                    id="table-rows"
                                    type="number"
                                    min={1}
                                    value={rows}
                                    onChange={(e) => setRows(parseInt(e.target.value, 10) || 0)}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="table-cols">Columns</Label>
                                <Input
                                    id="table-cols"
                                    type="number"
                                    min={1}
                                    value={cols}
                                    onChange={(e) => setCols(parseInt(e.target.value, 10) || 0)}
                                />
                            </div>
                        </div>

                        <label className="flex items-center gap-2 text-sm">
                            <input
                                type="checkbox"
                                checked={withHeaderRow}
                                onChange={(e) => setWithHeaderRow(e.target.checked)}
                                className="h-4 w-4 rounded border-border"
                            />
                            Include header row
                        </label>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
                            Cancel
                        </Button>
                        <Button type="submit">Insert</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
