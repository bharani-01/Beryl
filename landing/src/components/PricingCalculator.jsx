import React, { useState } from 'react';
import { Check, Zap, Sparkles, Shield, ArrowRight, DollarSign, TrendingDown } from 'lucide-react';

const PLANS = [
  {
    id: 'starter',
    name: 'Starter',
    badge: 'Popular for MVPs',
    priceMonthly: 15,
    description: 'Perfect for side projects, staging clusters, and early-stage products.',
    features: [
      '3 Running active resources',
      '1.0 vCPU allocation per container',
      '1 GB RAM per container',
      '20 GB custom NVMe storage quota',
      '5 persistent volume attachments',
      'Automated Let\'s Encrypt SSL',
      'Community & Discord support',
    ],
    highlight: false,
    ctaText: 'Deploy Starter',
  },
  {
    id: 'growth',
    name: 'Growth',
    badge: 'Most Scalable',
    priceMonthly: 39,
    description: 'Designed for production workloads and fast-growing teams.',
    features: [
      '10 Running active resources',
      '4.0 vCPU allocation per container',
      '8 GB RAM per container',
      '100 GB custom NVMe storage quota',
      '20 persistent volume attachments',
      'Hourly automated S3 backups',
      'Forensic SHA-256 audit vault',
      'Priority email & Slack support',
    ],
    highlight: true,
    ctaText: 'Deploy Growth',
  },
  {
    id: 'scale',
    name: 'Scale',
    badge: 'Enterprise Unlocked',
    priceMonthly: 99,
    description: 'Unlimited capacity for multi-region microservice fleets.',
    features: [
      'Unlimited running resources',
      '16.0 vCPU allocation per container',
      '32 GB RAM per container',
      '500 GB custom storage quota',
      'Unlimited volume attachments',
      'Multi-cloud server orchestration',
      'Dedicated compliance audit exports',
      '24/7 dedicated engineering SLA',
    ],
    highlight: false,
    ctaText: 'Deploy Scale',
  },
];

export default function PricingCalculator() {
  const [resourceCount, setResourceCount] = useState(6);

  // AWS equivalent cost estimation:
  // Fargate task ($15/task/mo) + RDS Postgres ($45) + ALB ($25) + NAT Gateway ($35) + CloudWatch ($20)
  const berylCost = resourceCount <= 3 ? 15 : resourceCount <= 10 ? 39 : 99;
  const awsEquivalent = Math.round(resourceCount * 32 + 85);
  const monthlySavings = awsEquivalent - berylCost;

  return (
    <section id="pricing" className="relative py-20 border-t border-white/[0.08] bg-[#07090e]">
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        
        {/* Section Header */}
        <div className="text-center max-w-3xl mx-auto mb-16">
          <div className="inline-flex items-center gap-2 rounded-md border border-emerald-500/20 bg-emerald-500/10 px-2.5 py-1 text-xs font-mono text-emerald-400 mb-3">
            <DollarSign className="size-3" />
            <span>TRANSPARENT CLOUD PRICING</span>
          </div>
          <h2 className="text-3xl sm:text-5xl font-extrabold tracking-tight text-white">
            Predictable Cloud Economics. <br />
            <span className="text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-teal-300">
              No surprise bandwidth bills.
            </span>
          </h2>
          <p className="mt-4 text-neutral-400 text-sm sm:text-base leading-relaxed max-w-xl mx-auto">
            Run on your own servers with zero egress markups. Simple flat-rate workspace subscriptions with transparent compute and container limits.
          </p>
        </div>

        {/* Hyperscaler Cost Comparison Widget */}
        <div className="mb-14 rounded-2xl border border-white/[0.1] bg-[#0c1018] p-6 sm:p-8 max-w-4xl mx-auto shadow-2xl">
          <div className="flex flex-col md:flex-row md:items-center justify-between gap-6 pb-6 border-b border-white/[0.08]">
            <div>
              <span className="text-xs font-mono text-emerald-400 font-bold uppercase tracking-wider">
                Interactive Cost Estimator
              </span>
              <h3 className="text-xl font-bold text-white mt-1">
                How many active containers & databases do you run?
              </h3>
            </div>
            <div className="flex items-center gap-2 font-mono text-2xl font-black text-white bg-white/[0.04] border border-white/[0.08] px-4 py-2 rounded-xl">
              <span className="text-emerald-400">{resourceCount}</span>
              <span className="text-xs text-neutral-400 font-normal">Active Resources</span>
            </div>
          </div>

          <div className="mt-6 space-y-3">
            <input
              type="range"
              min="1"
              max="25"
              value={resourceCount}
              onChange={(e) => setResourceCount(Number(e.target.value))}
              className="w-full h-2 bg-neutral-800 rounded-lg appearance-none cursor-pointer accent-emerald-400"
            />
            <div className="flex justify-between text-[11px] font-mono text-neutral-500">
              <span>1 Resource</span>
              <span>10 Resources (Growth)</span>
              <span>25+ Resources (Scale)</span>
            </div>
          </div>

          {/* Comparison Cards */}
          <div className="mt-8 grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div className="rounded-xl border border-emerald-500/30 bg-emerald-500/[0.04] p-4 text-center">
              <p className="text-xs text-emerald-400 font-mono font-medium">Beryl Flat Plan</p>
              <p className="text-3xl font-black text-white mt-1.5">${berylCost}<span className="text-xs font-normal text-neutral-400">/mo</span></p>
              <p className="text-[11px] text-neutral-400 mt-1">All features included</p>
            </div>

            <div className="rounded-xl border border-white/[0.08] bg-white/[0.02] p-4 text-center">
              <p className="text-xs text-neutral-400 font-mono font-medium">AWS ECS + RDS + NAT</p>
              <p className="text-3xl font-black text-neutral-300 mt-1.5">${awsEquivalent}<span className="text-xs font-normal text-neutral-500">/mo</span></p>
              <p className="text-[11px] text-neutral-500 mt-1">+ egress & IP taxes</p>
            </div>

            <div className="rounded-xl border border-emerald-500/40 bg-[#0f1717] p-4 text-center flex flex-col justify-center">
              <div className="flex items-center justify-center gap-1.5 text-xs text-emerald-400 font-mono font-bold">
                <TrendingDown className="size-3.5" />
                <span>Estimated Annual Savings</span>
              </div>
              <p className="text-3xl font-black text-emerald-400 mt-1.5">
                ${monthlySavings * 12}
                <span className="text-xs font-normal text-emerald-500/80">/yr</span>
              </p>
              <p className="text-[11px] text-neutral-400 mt-1">{Math.round((monthlySavings / awsEquivalent) * 100)}% cheaper</p>
            </div>
          </div>
        </div>

        {/* Pricing Cards Grid */}
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {PLANS.map((plan) => (
            <div
              key={plan.id}
              className={`relative rounded-2xl border p-7 transition-all flex flex-col justify-between ${
                plan.highlight
                  ? 'border-emerald-500/50 bg-[#0c121a] shadow-2xl shadow-emerald-500/10 scale-[1.02]'
                  : 'border-white/[0.08] bg-[#0b0e14] hover:border-white/[0.18]'
              }`}
            >
              {plan.highlight && (
                <div className="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full border border-emerald-500/40 bg-emerald-500 px-3 py-0.5 text-[11px] font-bold text-neutral-950 uppercase tracking-wider">
                  {plan.badge}
                </div>
              )}

              <div>
                <div className="flex items-center justify-between mb-4">
                  <h4 className="text-xl font-bold text-white">{plan.name}</h4>
                  {!plan.highlight && (
                    <span className="text-[11px] font-mono text-neutral-400 bg-white/[0.04] px-2 py-0.5 rounded">
                      {plan.badge}
                    </span>
                  )}
                </div>

                <p className="text-xs text-neutral-400 mb-6 leading-relaxed">
                  {plan.description}
                </p>

                <div className="flex items-baseline gap-1 mb-6 pb-6 border-b border-white/[0.08]">
                  <span className="text-4xl font-extrabold text-white">${plan.priceMonthly}</span>
                  <span className="text-xs font-mono text-neutral-400">/month</span>
                </div>

                {/* Features List */}
                <ul className="space-y-3 mb-8">
                  {plan.features.map((feat, fIdx) => (
                    <li key={fIdx} className="flex items-start gap-2.5 text-xs text-neutral-300">
                      <Check className="size-4 text-emerald-400 shrink-0 mt-0.5" />
                      <span>{feat}</span>
                    </li>
                  ))}
                </ul>
              </div>

              <a
                href="/login"
                className={`w-full inline-flex items-center justify-center gap-2 rounded-xl py-3 text-sm font-semibold transition-all ${
                  plan.highlight
                    ? 'bg-emerald-500 text-neutral-950 hover:bg-emerald-400 shadow-lg shadow-emerald-500/20'
                    : 'border border-white/[0.12] bg-white/[0.04] text-white hover:bg-white/[0.08]'
                }`}
              >
                <span>{plan.ctaText}</span>
                <ArrowRight className="size-4" />
              </a>
            </div>
          ))}
        </div>

      </div>
    </section>
  );
}
