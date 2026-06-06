import axios from 'axios';
import { useEffect, useMemo, useState } from 'react';
import { Image as ImageIcon, Upload as UploadIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { sanitizeUrl } from '@/utils/urlValidator';

type PickerFolder = {
    id: number;
    name: string;
    parent_id: number | null;
    is_folder: true;
};

type PickerMedia = {
    id: number;
    name: string;
    url: string;
    variant_urls: Record<string, string>;
    mime_type: string;
    size: string;
    created_at: string;
    is_folder: false;
};

type PickerPaginator<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    next_page_url: string | null;
    prev_page_url: string | null;
    per_page: number;
    total: number;
};

type PickerResponse = {
    folders: PickerFolder[];
    media: PickerPaginator<PickerMedia>;
    currentFolder: { id: number; name: string; parent_id: number | null } | null;
    breadcrumbs: { id: number; name: string }[];
};

type UploadedMedia = {
    id: number;
    name: string;
    url: string;
    variant_urls: Record<string, string>;
    mime_type: string;
    size: string;
};

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onSelectUrl: (url: string) => void;
    type?: string;
    enableUpload?: boolean;
};

export default function MediaLibraryDialog({
    open,
    onOpenChange,
    onSelectUrl,
    type = 'image',
    enableUpload = true,
}: Props) {
    const [activeTab, setActiveTab] = useState(enableUpload ? 'upload' : 'media');
    const [isLoadingLibrary, setIsLoadingLibrary] = useState(false);
    const [isUploading, setIsUploading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [folderId, setFolderId] = useState<number | null>(null);
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);
    const [data, setData] = useState<PickerResponse | null>(null);

    const mediaItems = useMemo(() => {
        return data?.media?.data ?? [];
    }, [data]);

    const fetchLibrary = async (params?: { folderId?: number | null; search?: string; page?: number }) => {
        setIsLoadingLibrary(true);
        setError(null);
        try {
            const response = await axios.get<PickerResponse>(route('admin.media.picker'), {
                params: {
                    folder_id: (params?.folderId ?? folderId) ?? undefined,
                    type,
                    search: (params?.search ?? search) || undefined,
                    page: params?.page ?? page,
                    per_page: 24,
                },
            });
            setData(response.data);
        } catch (e) {
            const message = e instanceof Error ? e.message : 'Gagal memuat media library';
            setError(message);
        } finally {
            setIsLoadingLibrary(false);
        }
    };

    const uploadFile = async (file: File) => {
        setIsUploading(true);
        setError(null);
        try {
            const formData = new FormData();
            formData.append('files[]', file);
            if (folderId) {
                formData.append('folder_id', String(folderId));
            }

            const response = await axios.post<UploadedMedia[]>(route('admin.media.store'), formData, {
                headers: {
                    Accept: 'application/json',
                },
            });

            const uploaded = response.data?.[0];
            if (!uploaded?.url) {
                throw new Error('Upload berhasil, tapi URL tidak tersedia');
            }
            onSelectUrl(sanitizeUrl(uploaded.url));
            onOpenChange(false);
        } catch (e) {
            const message = e instanceof Error ? e.message : 'Gagal upload media';
            setError(message);
        } finally {
            setIsUploading(false);
        }
    };

    useEffect(() => {
        if (!open) {
            return;
        }
        setPage(1);
        setFolderId(null);
        setSearch('');
        setData(null);
        setError(null);
        setActiveTab(enableUpload ? 'upload' : 'media');
    }, [open, enableUpload]);

    useEffect(() => {
        if (!open || activeTab !== 'media') {
            return;
        }
        fetchLibrary({ page: 1 });
    }, [open, activeTab]);

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-4xl">
                <DialogHeader>
                    <DialogTitle>Pilih Media</DialogTitle>
                </DialogHeader>

                <Tabs value={activeTab} onValueChange={setActiveTab} className="w-full">
                    <TabsList className="w-full">
                        {enableUpload && (
                            <TabsTrigger value="upload" className="w-full">
                                <UploadIcon className="w-4 h-4 mr-2" />
                                Upload
                            </TabsTrigger>
                        )}
                        <TabsTrigger value="media" className="w-full">
                            <ImageIcon className="w-4 h-4 mr-2" />
                            Media Library
                        </TabsTrigger>
                    </TabsList>

                    {enableUpload && (
                        <TabsContent value="upload" className="mt-4">
                            <div className="space-y-3">
                                <div className="border-2 border-dashed rounded-lg p-8 text-center">
                                    <Label htmlFor="media-upload" className="cursor-pointer">
                                        <UploadIcon className="mx-auto h-12 w-12 text-gray-400" />
                                        <span className="mt-2 block text-sm font-medium">
                                            Click to upload or drag & drop
                                        </span>
                                        <span className="mt-1 block text-xs text-gray-400">
                                            {type.toUpperCase()} files
                                        </span>
                                    </Label>
                                    <Input
                                        id="media-upload"
                                        type="file"
                                        className="hidden"
                                        disabled={isUploading}
                                        onChange={(e) => {
                                            const file = e.target.files?.[0];
                                            if (!file) {
                                                return;
                                            }
                                            void uploadFile(file);
                                        }}
                                        accept={`${type}/*`}
                                    />
                                </div>

                                {error && <div className="text-sm text-destructive">{error}</div>}
                            </div>
                        </TabsContent>
                    )}

                    <TabsContent value="media" className="mt-4">
                        <div className="space-y-3">
                            <div className="flex gap-2">
                                <Input
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="Cari media..."
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => {
                                        setPage(1);
                                        void fetchLibrary({ search, page: 1 });
                                    }}
                                    disabled={isLoadingLibrary}
                                >
                                    Cari
                                </Button>
                            </div>

                            {data?.breadcrumbs && data.breadcrumbs.length > 0 && (
                                <div className="flex flex-wrap gap-2 text-sm text-muted-foreground">
                                    <button
                                        type="button"
                                        className="underline"
                                        onClick={() => {
                                            setFolderId(null);
                                            setPage(1);
                                            void fetchLibrary({ folderId: null, page: 1 });
                                        }}
                                    >
                                        Root
                                    </button>
                                    {data.breadcrumbs.map((bc) => (
                                        <button
                                            key={bc.id}
                                            type="button"
                                            className="underline"
                                            onClick={() => {
                                                setFolderId(bc.id);
                                                setPage(1);
                                                void fetchLibrary({ folderId: bc.id, page: 1 });
                                            }}
                                        >
                                            / {bc.name}
                                        </button>
                                    ))}
                                </div>
                            )}

                            {error && <div className="text-sm text-destructive">{error}</div>}

                            <div className="grid grid-cols-4 gap-4">
                                {data?.folders?.map((folder) => (
                                    <button
                                        key={`folder-${folder.id}`}
                                        type="button"
                                        className="rounded-lg border p-3 text-left hover:bg-gray-900"
                                        onClick={() => {
                                            setFolderId(folder.id);
                                            setPage(1);
                                            void fetchLibrary({ folderId: folder.id, page: 1 });
                                        }}
                                    >
                                        <div className="flex items-center gap-2">
                                            <div className="w-10 h-10 bg-gray-800 rounded flex items-center justify-center">
                                                <ImageIcon className="w-5 h-5 text-gray-400" />
                                            </div>
                                            <div className="min-w-0">
                                                <div className="font-medium truncate">{folder.name}</div>
                                                <div className="text-xs text-muted-foreground">Folder</div>
                                            </div>
                                        </div>
                                    </button>
                                ))}

                                {mediaItems.map((item) => (
                                    <button
                                        key={item.id}
                                        type="button"
                                        className="relative group cursor-pointer rounded-lg overflow-hidden"
                                        onClick={() => {
                                            onSelectUrl(sanitizeUrl(item.url));
                                            onOpenChange(false);
                                        }}
                                    >
                                        <img
                                            src={sanitizeUrl(item.url)}
                                            alt={item.name}
                                            className="w-full h-28 object-cover"
                                        />
                                        <div className="absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                            <span className="text-white text-sm px-2 text-center line-clamp-2">
                                                {item.name}
                                            </span>
                                        </div>
                                    </button>
                                ))}
                            </div>

                            {data && (
                                <div className="flex justify-between items-center pt-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        disabled={isLoadingLibrary || !data.media.prev_page_url}
                                        onClick={() => {
                                            const nextPage = Math.max(1, data.media.current_page - 1);
                                            setPage(nextPage);
                                            void fetchLibrary({ page: nextPage });
                                        }}
                                    >
                                        Prev
                                    </Button>
                                    <div className="text-sm text-muted-foreground">
                                        Page {data.media.current_page} / {data.media.last_page}
                                    </div>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        disabled={isLoadingLibrary || !data.media.next_page_url}
                                        onClick={() => {
                                            const nextPage = data.media.current_page + 1;
                                            setPage(nextPage);
                                            void fetchLibrary({ page: nextPage });
                                        }}
                                    >
                                        Next
                                    </Button>
                                </div>
                            )}
                        </div>
                    </TabsContent>
                </Tabs>
            </DialogContent>
        </Dialog>
    );
}

