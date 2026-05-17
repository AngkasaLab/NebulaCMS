"use client"

import { ChevronRight } from "lucide-react"
import { Link, usePage } from "@inertiajs/react"
import {
  Collapsible,
  CollapsibleContent,
  CollapsibleTrigger,
} from "@/components/ui/collapsible"
import {
  SidebarGroup,
  SidebarGroupLabel,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarMenuSub,
  SidebarMenuSubButton,
  SidebarMenuSubItem,
} from "@/components/ui/sidebar"
import { type NavItem } from "@/types"

interface NavMainProps {
  items: Array<NavItem & { items?: Array<{ title: string; href: string }> }>
  label?: string
}

export function NavMain({ items, label }: NavMainProps) {
  const { url } = usePage()

  const normalizePath = (value?: string) => {
    if (!value) return "/"

    try {
      if (value.startsWith("http://") || value.startsWith("https://")) {
        value = new URL(value).pathname
      }
    } catch {
      // Keep the original value if URL parsing fails.
    }

    const [path] = value.split("?")
    const normalized = path.startsWith("/") ? path : `/${path}`

    return normalized !== "/" ? normalized.replace(/\/+$/, "") : normalized
  }

  const currentPath = normalizePath(url)

  const matchesPath = (candidate: string, current: string) => {
    if (candidate.endsWith("/*")) {
      const base = candidate.slice(0, -2) || "/"
      return current === base || current.startsWith(`${base}/`)
    }

    if (candidate === "/") return current === "/"
    return current === candidate || current.startsWith(`${candidate}/`)
  }

  const isUrlMatching = (itemUrl?: string, matchPaths?: string[]) => {
    if (!itemUrl) return false

    const candidates = [itemUrl, ...(matchPaths ?? [])].map(normalizePath)
    return candidates.some(candidate => matchesPath(candidate, currentPath))
  }

  return (
    <SidebarGroup>
      {label && <SidebarGroupLabel>{label}</SidebarGroupLabel>}
      <SidebarMenu>
        {items.map((item) => {
          const isActive = isUrlMatching(item.href, item.matchPaths)

          if (item.items && item.items.length > 0) {
            const hasActiveChild = item.items.some(subItem => isUrlMatching(subItem.href))
            return (
              <Collapsible key={item.title} asChild className="group/collapsible" defaultOpen={isActive || hasActiveChild}>
                <SidebarMenuItem>
                  <CollapsibleTrigger asChild>
                    <SidebarMenuButton tooltip={item.title} isActive={isActive || hasActiveChild}>
                      {item.icon && <item.icon />}
                      <span>{item.title}</span>
                      <ChevronRight className="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                    </SidebarMenuButton>
                  </CollapsibleTrigger>
                  <CollapsibleContent>
                    <SidebarMenuSub>
                      {item.items.map((subItem) => (
                        <SidebarMenuSubItem key={subItem.title}>
                          <SidebarMenuSubButton asChild isActive={isUrlMatching(subItem.href)}>
                            <Link href={subItem.href}>
                              <span>{subItem.title}</span>
                            </Link>
                          </SidebarMenuSubButton>
                        </SidebarMenuSubItem>
                      ))}
                    </SidebarMenuSub>
                  </CollapsibleContent>
                </SidebarMenuItem>
              </Collapsible>
            )
          }

          return (
            <SidebarMenuItem key={item.title}>
              <SidebarMenuButton asChild tooltip={item.title} isActive={isActive}>
                <Link href={item.href}>
                  {item.icon && <item.icon />}
                  <span>{item.title}</span>
                  {item.badge && (
                    <span className="ml-auto flex h-5 min-w-5 items-center justify-center rounded-full bg-emerald-500 px-2 text-[10px] font-medium text-white shadow-sm">
                      {typeof item.badge === 'string' ? item.badge : 'New'}
                    </span>
                  )}
                </Link>
              </SidebarMenuButton>
            </SidebarMenuItem>
          )
        })}
      </SidebarMenu>
    </SidebarGroup>
  )
}
