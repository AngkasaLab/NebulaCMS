import { Head, router, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { toast } from 'sonner';
import HeadingSmall from '@/components/heading-small';
import SettingsLayout from '@/layouts/settings/layout';
import { type BreadcrumbItem, type SharedData, type UpdateCheckResult } from '@/types';
import { useState } from 'react';
import { ReleaseNotesText } from '@/components/release-notes-text';
import { CheckCircle, Download, AlertTriangle, RotateCcw, Loader2, Shield, ExternalLink } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'System Update',
        href: '/settings/system-update',
    },
];

interface BackupItem {
    filename: string;
    path: string;
    size: number;
    created_at: string;
}

interface UpdatePageProps {
    currentVersion: string;
    backups: BackupItem[];
}

type UpdateSettingsPageProps = SharedData & UpdatePageProps;

type UpdateStep = 'idle' | 'checking' | 'checked' | 'updating' | 'done';

export default function Update({ currentVersion, backups }: UpdatePageProps) {
    const { props } = usePage<UpdateSettingsPageProps>();
    const flash = props.flash as { success?: string; error?: string; updateCheck?: UpdateCheckResult } | undefined;
    const updateAvailable = props.updateAvailable;

    const initialCheckResult = flash?.updateCheck ?? (updateAvailable?.available ? updateAvailable : null);
    const initialStep = initialCheckResult ? 'checked' : 'idle';

    const [step, setStep] = useState<UpdateStep>(initialStep);
    const [checkResult, setCheckResult] = useState<UpdateCheckResult | null>(initialCheckResult);
    const [isRollingBack, setIsRollingBack] = useState<string | null>(null);

    const handleCheck = () => {
        setStep('checking');
        router.post(route('admin.system.update.check'), {}, {
            preserveScroll: true,
            onSuccess: (page) => {
                const flash = page.props.flash as { success?: string; error?: string; updateCheck?: UpdateCheckResult } | undefined;
                if (flash?.updateCheck) {
                    setCheckResult(flash.updateCheck);
                    setStep('checked');
                }
            },
            onError: () => {
                setStep('idle');
                toast.error('Failed to check for updates');
            },
        });
    };

    const handleApply = () => {
        if (!checkResult?.download_url) return;

        setStep('updating');

        router.post(route('admin.system.update.apply'), {
            download_url: checkResult.download_url,
        }, {
            preserveScroll: true,
            onSuccess: (page) => {
                const flash = page.props.flash as { success?: string; error?: string; updateCheck?: UpdateCheckResult } | undefined;
                if (flash?.success) {
                    setStep('done');
                    toast.success(flash.success);
                } else if (flash?.error) {
                    setStep('checked');
                    toast.error(flash.error);
                }
            },
            onError: () => {
                setStep('checked');
                toast.error('Update failed. You can rollback to a previous version.');
            },
        });
    };

    const handleRollback = (backupPath: string) => {
        if (!confirm('Are you sure you want to rollback? This will restore your CMS to the backed up version.')) {
            return;
        }

        setIsRollingBack(backupPath);

        router.post(route('admin.system.update.rollback'), {
            backup_path: backupPath,
        }, {
            preserveScroll: true,
            onSuccess: (page) => {
                const flash = page.props.flash as { success?: string; error?: string; updateCheck?: UpdateCheckResult } | undefined;
                if (flash?.success) {
                    toast.success(flash.success);
                } else if (flash?.error) {
                    toast.error(flash.error);
                }
                setIsRollingBack(null);
            },
            onError: () => {
                toast.error('Rollback failed');
                setIsRollingBack(null);
            },
        });
    };

    const formatSize = (bytes: number) => {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1048576).toFixed(1) + ' MB';
    };

    const isProcessing = step === 'checking' || step === 'updating';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="System Update" />
            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall
                        title="System Update"
                        description="Check for and apply CMS updates"
                    />

                    {/* Current Version */}
                    <div className="rounded-lg border p-4">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-muted-foreground">Current Version</p>
                                <p className="text-2xl font-bold tracking-tight">v{currentVersion}</p>
                            </div>
                            <Button
                                onClick={handleCheck}
                                disabled={isProcessing}
                                variant="outline"
                            >
                                {step === 'checking' ? (
                                    <><Loader2 className="mr-2 h-4 w-4 animate-spin" />Checking...</>
                                ) : (
                                    'Check for updates'
                                )}
                            </Button>
                        </div>
                    </div>

                    {/* Progress indicator (matches real server work — backup, download, extract, migrate) */}
                    {step === 'updating' && (
                        <div className="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-950">
                            <div className="flex items-center gap-3">
                                <Loader2 className="h-5 w-5 animate-spin text-blue-600" />
                                <div>
                                    <p className="font-medium text-blue-900 dark:text-blue-100">
                                        Updating NebulaCMS…
                                    </p>
                                    <p className="text-sm text-blue-700 dark:text-blue-300">
                                        Creating a backup, downloading the release, and applying files. This may take several minutes — please
                                        do not close this page.
                                    </p>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Check Result */}
                    {checkResult && step === 'checked' && (
                        <>
                            {checkResult.error ? (
                                <div className="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-950">
                                    <div className="flex items-start gap-3">
                                        <AlertTriangle className="mt-0.5 h-5 w-5 text-red-600" />
                                        <div>
                                            <p className="font-medium text-red-900 dark:text-red-100">Error</p>
                                            <p className="text-sm text-red-700 dark:text-red-300">{checkResult.error}</p>
                                        </div>
                                    </div>
                                </div>
                            ) : checkResult.available ? (
                                <div className="rounded-lg border border-border bg-card p-4 shadow-sm">
                                    <div className="flex items-start gap-3">
                                        <Download className="mt-0.5 h-5 w-5 shrink-0 text-primary" />
                                        <div className="min-w-0 flex-1 space-y-3">
                                            <div>
                                                <p className="font-medium text-foreground">
                                                    Update available — v{checkResult.latest}
                                                </p>
                                                {checkResult.published_at && (
                                                    <p className="text-xs text-muted-foreground">
                                                        Released {new Date(checkResult.published_at).toLocaleDateString()}
                                                    </p>
                                                )}
                                            </div>

                                            {(checkResult.release_notes || checkResult.release_url) && (
                                                <div className="rounded-md border border-border bg-muted/40 px-3 py-2.5">
                                                    <p className="mb-1.5 text-xs font-medium text-muted-foreground">
                                                        What&apos;s new
                                                    </p>
                                                    {checkResult.release_notes ? (
                                                        <ReleaseNotesText
                                                            text={checkResult.release_notes}
                                                            className="text-sm leading-relaxed text-foreground"
                                                        />
                                                    ) : (
                                                        <p className="text-sm text-muted-foreground">
                                                            See GitHub for a summary of changes in this release.
                                                        </p>
                                                    )}
                                                    {checkResult.release_url && (
                                                        <a
                                                            href={checkResult.release_url}
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            className="mt-2 inline-flex items-center gap-1.5 text-sm font-medium text-primary hover:underline"
                                                        >
                                                            <ExternalLink className="h-3.5 w-3.5" />
                                                            Full release notes on GitHub
                                                        </a>
                                                    )}
                                                </div>
                                            )}

                                            <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-4">
                                                <Button onClick={handleApply} disabled={isProcessing}>
                                                    <Shield className="mr-2 h-4 w-4" />
                                                    Update to v{checkResult.latest}
                                                </Button>
                                                <p className="text-xs text-muted-foreground">
                                                    A backup will be created automatically before updating.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            ) : (
                                <div className="rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-950">
                                    <div className="flex items-center gap-3">
                                        <CheckCircle className="h-5 w-5 text-green-600" />
                                        <p className="font-medium text-green-900 dark:text-green-100">
                                            You&apos;re up to date! (v{checkResult.current})
                                        </p>
                                    </div>
                                </div>
                            )}
                        </>
                    )}

                    {/* Success */}
                    {step === 'done' && (
                        <div className="rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-950">
                            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div className="flex items-center gap-3">
                                    <CheckCircle className="h-5 w-5 shrink-0 text-green-600" />
                                    <p className="font-medium text-green-900 dark:text-green-100">Update applied successfully.</p>
                                </div>
                                <Button type="button" onClick={() => window.location.reload()}>
                                    Reload page
                                </Button>
                            </div>
                        </div>
                    )}

                    {/* Backups */}
                    {backups.length > 0 && (
                        <div className="space-y-3">
                            <HeadingSmall
                                title="Backups"
                                description="Pre-update backups. You can rollback to any of these."
                            />
                            <div className="divide-y rounded-lg border">
                                {backups.map((backup) => (
                                    <div key={backup.filename} className="flex items-center justify-between p-3">
                                        <div>
                                            <p className="text-sm font-medium">{backup.filename}</p>
                                            <p className="text-xs text-muted-foreground">
                                                {backup.created_at} · {formatSize(backup.size)}
                                            </p>
                                        </div>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => handleRollback(backup.path)}
                                            disabled={isRollingBack !== null}
                                        >
                                            {isRollingBack === backup.path ? (
                                                <><Loader2 className="mr-2 h-3 w-3 animate-spin" />Rolling back...</>
                                            ) : (
                                                <><RotateCcw className="mr-2 h-3 w-3" />Rollback</>
                                            )}
                                        </Button>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}

                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
