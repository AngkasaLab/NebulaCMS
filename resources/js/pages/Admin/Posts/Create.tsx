import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import Form from './Form';

interface Category {
    id: number;
    name: string;
}

interface Tag {
    id: number;
    name: string;
    color: string;
}

interface Media {
    id: number;
    name: string;
}

interface Props {
    categories: Category[];
    tags: Tag[];
    media?: Media[];
}

const breadcrumbs = [
    {
        title: 'Dashboard',
        href: route('admin.dashboard'),
    },
    {
        title: 'Posts',
        href: route('admin.posts.index'),
    },
    {
        title: 'Create',
    },
];

export default function Create({ categories, tags, media }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Post" />
            <div className="p-4">
            <Form categories={categories} tags={tags} media={media ?? []} />
            </div>
        </AppLayout>
    );
}
