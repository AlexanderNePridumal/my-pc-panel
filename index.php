<script>

async function loadDevices()
{
    try
    {
        const response = await fetch(window.location.href);

        const html = await response.text();

        const parser = new DOMParser();

        const doc = parser.parseFromString(html, "text/html");

        const newContainer =
            doc.querySelector(".container");

        const currentContainer =
            document.querySelector(".container");

        if(newContainer && currentContainer)
        {
            currentContainer.innerHTML =
                newContainer.innerHTML;
        }

        const refresh =
            document.querySelector(".refresh");

        if(refresh)
        {
            refresh.innerText =
                "Updated: " +
                new Date().toLocaleTimeString();
        }
    }
    catch(err)
    {
        console.log(err);
    }
}

function sendForm(form)
{
    const button =
        form.querySelector("button");

    const loading =
        form.querySelector(".loading");

    button.disabled = true;

    button.innerText = "Отправка...";

    loading.style.display = "block";

    return true;
}

setInterval(loadDevices, 5000);

</script>
