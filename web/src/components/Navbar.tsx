"use client";

import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { useAuth } from "@/context/AuthContext";

const NAV = [
  { href: "/dashboard", label: "Home",    icon: "🏠" },
  { href: "/rewards",   label: "Rewards", icon: "🎁" },
  { href: "/history",   label: "History", icon: "📋" },
];

export default function Navbar() {
  const pathname = usePathname();
  const { signOut, customer } = useAuth();
  const router = useRouter();

  const handleSignOut = async () => {
    await signOut();
    router.replace("/");
  };

  return (
    <>
      {/* Top bar */}
      <header className="bg-white border-b border-gray-200 px-4 py-3 flex justify-between items-center sticky top-0 z-10">
        <span className="font-extrabold text-yellow-600 text-lg">⭐ LoyalteApp</span>
        <div className="flex items-center gap-3">
          {customer && (
            <span className="text-sm text-gray-500 hidden sm:block">
              {customer.memberId}
            </span>
          )}
          <button
            onClick={handleSignOut}
            className="text-sm text-gray-500 hover:text-red-500 font-medium"
          >
            Sign Out
          </button>
        </div>
      </header>

      {/* Bottom navigation */}
      <nav className="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 flex z-10 safe-area-inset-bottom">
        {NAV.map(({ href, label, icon }) => {
          const active = pathname === href;
          return (
            <Link
              key={href}
              href={href}
              className={`flex-1 flex flex-col items-center py-3 text-xs font-medium transition-colors ${
                active ? "text-yellow-600" : "text-gray-400 hover:text-gray-600"
              }`}
            >
              <span className="text-xl mb-0.5">{icon}</span>
              <span>{label}</span>
              {active && (
                <span className="w-1 h-1 bg-yellow-500 rounded-full mt-0.5" />
              )}
            </Link>
          );
        })}
      </nav>

      {/* Spacer so content is not hidden behind bottom nav */}
      <div className="h-16" />
    </>
  );
}
