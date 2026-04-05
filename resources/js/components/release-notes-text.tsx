/**
 * Renders plain release-note text with clickable URLs (GitHub release bodies are often Markdown + links).
 */
export function ReleaseNotesText({ text, className }: { text: string; className?: string }) {
    const parts = text.split(/(https?:\/\/[^\s<>"']+)/g);

    return (
        <p className={className}>
            {parts.map((part, i) =>
                /^https?:\/\//.test(part) ? (
                    <a
                        key={i}
                        href={part}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="font-medium text-primary underline underline-offset-2 hover:underline"
                    >
                        {part}
                    </a>
                ) : (
                    <span key={i}>{part}</span>
                ),
            )}
        </p>
    );
}
