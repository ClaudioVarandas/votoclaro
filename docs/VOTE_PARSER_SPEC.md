# Write vote detail parsing logic


Because that detalhe field is… HTML-flavored chaos wrapped in parliamentary formality.

data

```shell
A Favor: <I>PSD</I>, <I> CH</I>, <I> PS</I>, <I> CDS-PP</I>, <I> JPP</I><BR>
Contra:<I>L</I>, <I> PCP</I>, <I> BE</I><BR>
Abstenção:<I>IL</I>, <I> PAN</I>

```


We want this clean output:

```shell
[
    'PSD' => 'favor',
    'CH' => 'favor',
    'PS' => 'favor',
    'CDS-PP' => 'favor',
    'JPP' => 'favor',
    'L' => 'contra',
    'PCP' => 'contra',
    'BE' => 'contra',
    'IL' => 'abstencao',
    'PAN' => 'abstencao',
]

```

- No HTML. 
- No whitespace weirdness.
- No duplicate commas. 
- No accidental empty entries.

🧠 Strategy

Instead of messy string splitting, we:

- Normalize `<BR>` → newline
- Strip all HTML tags
- Normalize accents & labels
- Extract each block with regex
- Clean party names
- Return associative array
- Clean. Deterministic. Defensive.
