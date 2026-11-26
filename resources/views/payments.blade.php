<x-admin-layout title="Платежи">
    <div class="space-y-6">
        <section class="rounded-xl bg-white p-6 shadow-sm">
            <header class="mb-4 border-b border-slate-200 pb-4">
                <h1 class="text-2xl font-bold">Платежи</h1>
                <p class="text-sm text-slate-500">Стоимость и способы пополнения баланса</p>
            </header>
            <h2 class="text-xl font-semibold">Сколько стоит</h2>
            <p class="mt-3 leading-7 text-slate-700">
                В дополнение к 30 бесплатным поднятиям, которых хватит на поднятие 1 темы каждый день в течение месяца, вы можете купить дополнительные поднятия.
            </p>
            <p class="mt-3 rounded-lg bg-slate-50 p-4 font-medium text-slate-800">
                1 сом равняется 3 дополнительным поднятиям (up)
            </p>
            <ul class="mt-4 space-y-3">
                <li class="rounded-lg border border-slate-200 p-4">
                    <strong>10 сом</strong> (30 поднятий) достаточно, чтобы поднимать 1 дополнительную тему раз в сутки в течение месяца (итого 2 темы).
                </li>
                <li class="rounded-lg border border-slate-200 p-4">
                    <strong>20 сом</strong> (60 поднятий) достаточно, чтобы поднимать 2 дополнительные темы раз в сутки в течение месяца (итого 3 темы).
                </li>
                <li class="rounded-lg border border-slate-200 p-4">
                    <strong>50 сом</strong> (150 поднятий) достаточно, чтобы поднимать 5 дополнительных тем раз в сутки в течение месяца (итого 6 тем).
                </li>
                <li class="text-sm text-slate-500">И так далее.</li>
            </ul>
        </section>

        <section class="rounded-xl bg-white p-6 shadow-sm">
            <h2 class="text-xl font-semibold">Варианты пополнения баланса</h2>
            <ul class="mt-4 space-y-3">
                <li class="rounded-lg border border-slate-200 p-4">
                    <h3 class="font-semibold">О!Деньги</h3>
                    <p class="mt-1 text-slate-700">Пополнить электронный кошелёк О!Деньги: <a class="font-medium text-blue-700 hover:underline" href="tel:0707844640">0707 84-46-40</a></p>
                </li>
                <li class="rounded-lg border border-slate-200 p-4">
                    <h3 class="font-semibold">Баланс номера О!</h3>
                    <p class="mt-1 text-slate-700">Пополнить баланс номера О!: <a class="font-medium text-blue-700 hover:underline" href="tel:0707844640">0707 84-46-40</a></p>
                </li>
            </ul>
        </section>

        <section class="rounded-xl border border-amber-200 bg-amber-50 p-6 text-amber-950">
            <p class="leading-7">
                После пополнения необходимо создать запрос на платеж, указав данные пополнения (когда и на какую сумму). После проверки администратором ваш платеж будет подтверждён и зачислен на баланс.
            </p>
        </section>
    </div>
</x-admin-layout>
