import { useState, useEffect, useRef, useCallback } from 'react';
import axios from 'axios';

export type SaveStatus = 'idle' | 'saving' | 'saved' | 'error';

export type PreviewLinks = {
    url: string;
    signed_url: string;
};

interface AutoSaveOptions {
    /** Delay in milliseconds before triggering auto-save (default: 3000) */
    debounceMs?: number;
    /** Callback when save is successful */
    onSuccess?: (postId: number, savedAt: string, preview?: PreviewLinks) => void;
    /** Callback when save fails */
    onError?: (error: string) => void;
}

interface AutoSaveData {
    title?: string;
    content?: string;
    excerpt?: string;
    category_id?: string;
    featured_image_id?: string;
    tags?: string[];
}

type AutoSaveResponse = {
    success: boolean;
    post_id: number;
    saved_at: string;
    message?: string;
    preview?: PreviewLinks;
};

interface UseAutoSaveReturn {
    /** Current save status */
    status: SaveStatus;
    /** Last saved timestamp */
    lastSavedAt: string | null;
    /** Current post ID (updated after first save) */
    postId: number | null;
    /** Error message if save failed */
    error: string | null;
    /** Trigger save with current data */
    triggerSave: (data: AutoSaveData) => void;
    /** Reset the auto-save state */
    reset: () => void;
}

export function useAutoSave(
    initialPostId: number | null = null,
    options: AutoSaveOptions = {}
): UseAutoSaveReturn {
    const { debounceMs = 3000, onSuccess, onError } = options;

    const [status, setStatus] = useState<SaveStatus>('idle');
    const [lastSavedAt, setLastSavedAt] = useState<string | null>(null);
    const [postId, setPostId] = useState<number | null>(initialPostId);
    const [error, setError] = useState<string | null>(null);

    const timeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);
    const resetStatusTimeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);
    const pendingDataRef = useRef<AutoSaveData | null>(null);
    const isSavingRef = useRef(false);
    const abortControllerRef = useRef<AbortController | null>(null);

    const performSave = useCallback(async (data: AutoSaveData) => {
        if (isSavingRef.current) {
            // Queue this data for next save
            pendingDataRef.current = data;
            return;
        }

        isSavingRef.current = true;
        setStatus('saving');
        setError(null);

        try {
            const url = postId
                ? route('admin.posts.autosave.update', { post: postId })
                : route('admin.posts.autosave');

            if (abortControllerRef.current) {
                abortControllerRef.current.abort();
            }
            const abortController = new AbortController();
            abortControllerRef.current = abortController;

            const response = await axios.post<AutoSaveResponse>(url, data, {
                signal: abortController.signal,
            });

            if (response.data.success) {
                setPostId(response.data.post_id);
                setLastSavedAt(response.data.saved_at);
                setStatus('saved');
                onSuccess?.(response.data.post_id, response.data.saved_at, response.data.preview);

                // Reset to idle after 2 seconds
                if (resetStatusTimeoutRef.current) {
                    clearTimeout(resetStatusTimeoutRef.current);
                }
                resetStatusTimeoutRef.current = setTimeout(() => {
                    setStatus((prev) => (prev === 'saved' ? 'idle' : prev));
                }, 2000);
            } else {
                throw new Error(response.data.message || 'Failed to save');
            }
        } catch (err) {
            if (axios.isCancel(err)) {
                return;
            }

            if (err instanceof Error && err.name === 'CanceledError') {
                return;
            }

            const errorMessage = err instanceof Error ? err.message : 'Failed to save draft';
            setError(errorMessage);
            setStatus('error');
            onError?.(errorMessage);
        } finally {
            isSavingRef.current = false;
            abortControllerRef.current = null;

            // If there's pending data, save it
            if (pendingDataRef.current) {
                const pendingData = pendingDataRef.current;
                pendingDataRef.current = null;
                performSave(pendingData);
            }
        }
    }, [postId, onSuccess, onError]);

    const triggerSave = useCallback((data: AutoSaveData) => {
        // Clear existing timeout
        if (timeoutRef.current) {
            clearTimeout(timeoutRef.current);
        }

        // Set new timeout for debounced save
        timeoutRef.current = setTimeout(() => {
            performSave(data);
        }, debounceMs);
    }, [debounceMs, performSave]);

    const reset = useCallback(() => {
        if (timeoutRef.current) {
            clearTimeout(timeoutRef.current);
        }
        if (resetStatusTimeoutRef.current) {
            clearTimeout(resetStatusTimeoutRef.current);
        }
        if (abortControllerRef.current) {
            abortControllerRef.current.abort();
            abortControllerRef.current = null;
        }
        setStatus('idle');
        setLastSavedAt(null);
        setError(null);
        pendingDataRef.current = null;
    }, []);

    // Cleanup on unmount
    useEffect(() => {
        return () => {
            if (timeoutRef.current) {
                clearTimeout(timeoutRef.current);
            }
            if (resetStatusTimeoutRef.current) {
                clearTimeout(resetStatusTimeoutRef.current);
            }
            if (abortControllerRef.current) {
                abortControllerRef.current.abort();
                abortControllerRef.current = null;
            }
        };
    }, []);

    // Update postId if initialPostId changes
    useEffect(() => {
        if (initialPostId !== null) {
            setPostId(initialPostId);
        }
    }, [initialPostId]);

    return {
        status,
        lastSavedAt,
        postId,
        error,
        triggerSave,
        reset,
    };
}
