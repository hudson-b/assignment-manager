N = input("How many goats?")
K = input("How many apples?")
K = int(K)
N = int(N)
apples = K/N
remainder = K % N
apples = int(apples)
print("Each goat will receive", apples, "apples.")
print(remainder, "apples will be left in the basket.")
