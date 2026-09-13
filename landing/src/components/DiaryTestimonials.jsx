import React from 'react';

const ENTRIES = [
  {
    date: 'September 3rd',
    content: 'Migrated our studio’s store and API from AWS to our own Hetzner node via Beryl. The bill dropped from $380/mo to $18/mo, but honestly the peace of mind of not debugging CloudWatch was the real reward.',
    author: 'Elena Rostova',
    role: 'Founder, Atelier Design',
    rotation: '-rotate-1',
  },
  {
    date: 'August 28th',
    content: 'It truly feels like a digital living room. I push my code to main, look up from my coffee, and it’s already serving live with automatic TLS. No Terraform headache, no mysterious egress line items.',
    author: 'Liam Vance',
    role: 'Fullstack Builder',
    rotation: 'rotate-1',
  },
  {
    date: 'August 14th',
    content: 'Having automated S3 backups for our Postgres databases running quietly without complicated cron setups allowed me to actually sleep through the night during our launch week.',
    author: 'Sara Chen',
    role: 'CTO, Paperclip Media',
    rotation: 'rotate-1',
  },
  {
    date: 'July 29th',
    content: 'We set up our entire production stack—Next.js, Redis, and Supabase—in under 10 minutes. It feels tactile, simple, and respectful of an engineer’s mental bandwidth.',
    author: 'Kaelen Miller',
    role: 'Independent Developer',
    rotation: '-rotate-1',
  },
];

export default function DiaryTestimonials() {
  return (
    <section id="testimonials" className="py-20 sm:py-28 bg-[#F8F7F2]/60 border-t border-stone-200/60">
      <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        
        {/* Section Header */}
        <div className="text-center max-w-xl mx-auto mb-16">
          <span className="font-cursive text-3xl text-stone-400 block mb-1">
            notes from the community
          </span>
          <h2 className="text-3xl sm:text-4xl font-medium tracking-tight text-[#292524]">
            Diary entries from real builders
          </h2>
          <p className="text-sm text-[#78716C] mt-2">
            Thoughts scribbled down after letting go of cloud over-engineering.
          </p>
        </div>

        {/* Two-column grid with slight rotation */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
          {ENTRIES.map((entry, idx) => (
            <div
              key={idx}
              className={`rounded-3xl bg-white p-7 sm:p-8 shadow-[0_4px_20px_-2px_rgba(0,0,0,0.04)] border border-stone-100 ${entry.rotation} hover:rotate-0 transition-transform duration-300 flex flex-col justify-between`}
            >
              <div>
                <span className="text-xs font-normal text-stone-400 block mb-4">
                  {entry.date}
                </span>
                <p className="text-[15px] sm:text-base text-[#292524] leading-relaxed font-normal">
                  "{entry.content}"
                </p>
              </div>

              {/* Signature style: 32px horizontal line followed by Reenie Beanie cursive text in 24px stone-500 */}
              <div className="mt-8 pt-4 flex items-center gap-3">
                <div className="w-8 h-[1.5px] bg-stone-300"></div>
                <span className="font-cursive text-2xl text-stone-500">
                  {entry.author}, {entry.role}
                </span>
              </div>
            </div>
          ))}
        </div>

      </div>
    </section>
  );
}
